<?php

namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\InventoryBalance;
use App\Models\PurchaseRefund;
use App\Models\PurchaseRefundItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProviderRefundService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function create(Batch $batch, array $data): PurchaseRefund
    {
        $items = $data['items'];

        foreach ($items as $index => $item) {
            $items[$index]['_request_index'] = $index;
        }

        usort($items, fn (array $left, array $right): int => $left['batch_item_id'] <=> $right['batch_item_id']);

        return DB::transaction(function () use ($batch, $data, $items): PurchaseRefund {
            $lockedBatch = Batch::query()->lockForUpdate()->findOrFail($batch->getKey());

            if ($lockedBatch->status === BatchStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'batch' => ['A cancelled batch cannot be refunded.'],
                ]);
            }

            $batchItems = BatchItem::query()
                ->where('batch_id', $lockedBatch->getKey())
                ->whereIn('id', array_column($items, 'batch_item_id'))
                ->get()
                ->keyBy('id');

            if ($batchItems->count() !== count($items)) {
                throw ValidationException::withMessages([
                    'items' => ['One or more batch items do not belong to this batch.'],
                ]);
            }

            $balances = InventoryBalance::query()
                ->whereIn('batch_item_id', array_column($items, 'batch_item_id'))
                ->orderBy('batch_item_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('batch_item_id');

            $this->validateAvailableQuantities($items, $balances);

            $refundedAt = isset($data['refunded_at'])
                ? CarbonImmutable::parse($data['refunded_at'])
                : CarbonImmutable::now();

            $refund = PurchaseRefund::query()->create([
                'batch_id' => $lockedBatch->getKey(),
                'provider_id' => $lockedBatch->provider_id,
                'refunded_at' => $refundedAt,
                'reason' => $data['reason'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);

            foreach ($items as $item) {
                /** @var BatchItem $batchItem */
                $batchItem = $batchItems->get($item['batch_item_id']);
                $refundItem = PurchaseRefundItem::query()->create([
                    'purchase_refund_id' => $refund->getKey(),
                    'batch_item_id' => $batchItem->getKey(),
                    'product_id' => $batchItem->product_id,
                    'storage_id' => $batchItem->storage_id,
                    'qty' => $item['qty'],
                    'unit_refund_cost' => isset($item['unit_refund_cost'])
                        ? (string) $item['unit_refund_cost']
                        : (string) $batchItem->unit_cost,
                ]);

                $this->inventoryService->decreaseAvailable(
                    $batchItem,
                    $item['qty'],
                    StockMovementType::ProviderRefundOut,
                    $refundedAt,
                    PurchaseRefundItem::class,
                    $refundItem->getKey(),
                );
            }

            $this->updateBatchStatus($lockedBatch);

            return $refund->load('items');
        });
    }

    private function validateAvailableQuantities(array $items, Collection $balances): void
    {
        foreach ($items as $item) {
            $availableQty = $balances->get($item['batch_item_id'])?->qty_available ?? 0;

            if ($item['qty'] > $availableQty) {
                throw ValidationException::withMessages([
                    "items.{$item['_request_index']}.qty" => [
                        "Requested quantity is {$item['qty']}, but only {$availableQty} units are available.",
                    ],
                ]);
            }
        }
    }

    private function updateBatchStatus(Batch $batch): void
    {
        $purchasedQty = (int) $batch->items()->sum('purchased_qty');
        $providerRefundedQty = (int) PurchaseRefundItem::query()
            ->join('batch_items', 'batch_items.id', '=', 'purchase_refund_items.batch_item_id')
            ->where('batch_items.batch_id', $batch->getKey())
            ->sum('purchase_refund_items.qty');

        $batch->update([
            'status' => $providerRefundedQty === $purchasedQty
                ? BatchStatus::FullyRefunded
                : BatchStatus::PartiallyRefunded,
        ]);
    }
}
