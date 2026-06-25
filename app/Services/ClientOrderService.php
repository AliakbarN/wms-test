<?php

namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\ClientOrderStatus;
use App\Enums\StockMovementType;
use App\Models\BatchItem;
use App\Models\Client;
use App\Models\ClientOrder;
use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use App\Models\InventoryBalance;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientOrderService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function create(array $data): ClientOrder
    {
        $requestedProducts = $data['products'];

        foreach ($requestedProducts as $index => $requestedProduct) {
            $requestedProducts[$index]['_request_index'] = $index;
        }

        usort(
            $requestedProducts,
            fn (array $left, array $right): int => $left['id'] <=> $right['id'],
        );

        return DB::transaction(function () use ($data, $requestedProducts): ClientOrder {
            $client = Client::query()
                ->whereKey($data['client_id'])
                ->where('is_active', true)
                ->first();

            if ($client === null) {
                throw ValidationException::withMessages([
                    'client_id' => ['The selected client is invalid or inactive.'],
                ]);
            }

            $orderedAt = isset($data['ordered_at'])
                ? CarbonImmutable::parse($data['ordered_at'])
                : CarbonImmutable::now();

            $order = ClientOrder::query()->create([
                'client_id' => $client->getKey(),
                'ordered_at' => $orderedAt,
                'status' => ClientOrderStatus::Confirmed,
                'idempotency_key' => $data['idempotency_key'] ?? null,
            ]);

            foreach ($requestedProducts as $requestedProduct) {
                $product = Product::query()
                    ->whereKey($requestedProduct['id'])
                    ->where('is_active', true)
                    ->first();

                if ($product === null) {
                    throw ValidationException::withMessages([
                        "products.{$requestedProduct['_request_index']}.id" => [
                            'The selected product is invalid or inactive.',
                        ],
                    ]);
                }

                $orderItem = ClientOrderItem::query()->create([
                    'order_id' => $order->getKey(),
                    'product_id' => $product->getKey(),
                    'requested_qty' => $requestedProduct['qty'],
                    'unit_sale_price' => (string) $product->default_sale_price,
                ]);

                $this->allocateProduct(
                    $orderItem,
                    $product,
                    $requestedProduct['qty'],
                    $orderedAt,
                    $requestedProduct['_request_index'],
                );
            }

            return $order->load('items.allocations.batchItem:id,batch_id');
        });
    }

    private function allocateProduct(
        ClientOrderItem $orderItem,
        Product $product,
        int $requestedQty,
        CarbonImmutable $occurredAt,
        int $requestIndex,
    ): void {
        $remainingQty = $requestedQty;

        while ($remainingQty > 0) {
            $balance = $this->nextFifoBalance($product);

            if ($balance === null) {
                $availableQty = $requestedQty - $remainingQty;

                throw ValidationException::withMessages([
                    "products.{$requestIndex}.qty" => [
                        "Requested quantity is {$requestedQty}, but only {$availableQty} units are available.",
                    ],
                ]);
            }

            $allocationQty = min($remainingQty, $balance->qty_available);
            $batchItem = BatchItem::query()->findOrFail($balance->batch_item_id);

            $allocation = ClientOrderAllocation::query()->create([
                'order_item_id' => $orderItem->getKey(),
                'batch_item_id' => $batchItem->getKey(),
                'product_id' => $product->getKey(),
                'storage_id' => $balance->storage_id,
                'qty' => $allocationQty,
                'unit_cost' => (string) $batchItem->unit_cost,
                'unit_sale_price' => (string) $orderItem->unit_sale_price,
            ]);

            $this->inventoryService->decreaseAvailable(
                $batchItem,
                $allocationQty,
                StockMovementType::SaleOut,
                $occurredAt,
                ClientOrderAllocation::class,
                $allocation->getKey(),
            );

            $remainingQty -= $allocationQty;
        }
    }

    private function nextFifoBalance(Product $product): ?InventoryBalance
    {
        return InventoryBalance::query()
            ->join('batch_items', 'batch_items.id', '=', 'inventory_balances.batch_item_id')
            ->join('batches', 'batches.id', '=', 'batch_items.batch_id')
            ->join('storages', 'storages.id', '=', 'inventory_balances.storage_id')
            ->where('inventory_balances.product_id', $product->getKey())
            ->where('inventory_balances.qty_available', '>', 0)
            ->where('storages.is_active', true)
            ->whereIn('batches.status', [
                BatchStatus::Confirmed->value,
                BatchStatus::PartiallyRefunded->value,
            ])
            ->orderBy('batches.purchased_at')
            ->orderBy('batches.id')
            ->orderBy('batch_items.id')
            ->select('inventory_balances.*')
            ->lock('for update of inventory_balances')
            ->first();
    }
}
