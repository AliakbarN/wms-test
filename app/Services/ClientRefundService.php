<?php

namespace App\Services;

use App\Enums\ClientOrderStatus;
use App\Enums\StockMovementType;
use App\Models\BatchItem;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use App\Models\ClientRefund;
use App\Models\ClientRefundItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientRefundService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function create(ClientOrder $order, array $data): ClientRefund
    {
        $items = $data['items'];

        foreach ($items as $index => $item) {
            $items[$index]['_request_index'] = $index;
        }

        usort($items, fn (array $left, array $right): int => $left['order_allocation_id'] <=> $right['order_allocation_id']);

        return DB::transaction(function () use ($order, $data, $items): ClientRefund {
            $lockedOrder = ClientOrder::query()->lockForUpdate()->findOrFail($order->getKey());

            if (! in_array($lockedOrder->status, [
                ClientOrderStatus::Confirmed,
                ClientOrderStatus::PartiallyRefunded,
            ], true)) {
                throw ValidationException::withMessages([
                    'order' => ['This order cannot be refunded.'],
                ]);
            }

            $allocationIds = array_column($items, 'order_allocation_id');
            $allocations = ClientOrderAllocation::query()
                ->whereIn('id', $allocationIds)
                ->whereHas('orderItem', fn ($query) => $query->where('order_id', $lockedOrder->getKey()))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($allocations->count() !== count($items)) {
                throw ValidationException::withMessages([
                    'items' => ['One or more allocations do not belong to this order.'],
                ]);
            }

            $previousRefunds = ClientRefundItem::query()
                ->whereIn('order_allocation_id', $allocationIds)
                ->selectRaw('order_allocation_id, SUM(qty) AS refunded_qty')
                ->groupBy('order_allocation_id')
                ->pluck('refunded_qty', 'order_allocation_id');

            $this->validateRefundableQuantities($items, $allocations, $previousRefunds);

            $refundedAt = isset($data['refunded_at'])
                ? CarbonImmutable::parse($data['refunded_at'])
                : CarbonImmutable::now();

            $refund = ClientRefund::query()->create([
                'order_id' => $lockedOrder->getKey(),
                'client_id' => $lockedOrder->client_id,
                'refunded_at' => $refundedAt,
                'reason' => $data['reason'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);

            foreach ($items as $item) {
                /** @var ClientOrderAllocation $allocation */
                $allocation = $allocations->get($item['order_allocation_id']);
                $restock = $item['restock'] ?? true;
                $refundItem = ClientRefundItem::query()->create([
                    'client_refund_id' => $refund->getKey(),
                    'order_allocation_id' => $allocation->getKey(),
                    'product_id' => $allocation->product_id,
                    'batch_item_id' => $allocation->batch_item_id,
                    'storage_id' => $allocation->storage_id,
                    'qty' => $item['qty'],
                    'unit_sale_price' => (string) $allocation->unit_sale_price,
                    'unit_cost' => (string) $allocation->unit_cost,
                    'restock' => $restock,
                ]);

                if ($restock) {
                    $batchItem = BatchItem::query()->findOrFail($allocation->batch_item_id);
                    $this->inventoryService->increaseAvailable(
                        $batchItem,
                        $item['qty'],
                        StockMovementType::ClientRefundIn,
                        $refundedAt,
                        ClientRefundItem::class,
                        $refundItem->getKey(),
                    );
                }
            }

            $this->updateOrderStatus($lockedOrder);

            return $refund->load('items');
        });
    }

    private function validateRefundableQuantities(
        array $items,
        Collection $allocations,
        Collection $previousRefunds,
    ): void {
        foreach ($items as $item) {
            $allocation = $allocations->get($item['order_allocation_id']);
            $refundableQty = $allocation->qty - (int) $previousRefunds->get($allocation->getKey(), 0);

            if ($item['qty'] > $refundableQty) {
                throw ValidationException::withMessages([
                    "items.{$item['_request_index']}.qty" => [
                        "Requested quantity is {$item['qty']}, but only {$refundableQty} units are refundable.",
                    ],
                ]);
            }
        }
    }

    private function updateOrderStatus(ClientOrder $order): void
    {
        $allocatedQty = (int) ClientOrderAllocation::query()
            ->whereHas('orderItem', fn ($query) => $query->where('order_id', $order->getKey()))
            ->sum('qty');
        $refundedQty = (int) ClientRefundItem::query()
            ->join('client_order_allocations', 'client_order_allocations.id', '=', 'client_refund_items.order_allocation_id')
            ->join('client_order_items', 'client_order_items.id', '=', 'client_order_allocations.order_item_id')
            ->where('client_order_items.order_id', $order->getKey())
            ->sum('client_refund_items.qty');

        $order->update([
            'status' => $refundedQty === $allocatedQty
                ? ClientOrderStatus::FullyRefunded
                : ClientOrderStatus::PartiallyRefunded,
        ]);
    }
}
