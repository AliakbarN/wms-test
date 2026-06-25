<?php

namespace App\Services;

use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Storage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function create(array $data): Batch
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;

        return DB::transaction(function () use ($data, $idempotencyKey): Batch {
            $provider = Provider::query()
                ->whereKey($data['provider_id'])
                ->where('is_active', true)
                ->first();

            if ($provider === null) {
                throw ValidationException::withMessages([
                    'provider_id' => ['The selected provider is invalid or inactive.'],
                ]);
            }

            $purchasedAt = isset($data['purchased_at'])
                ? CarbonImmutable::parse($data['purchased_at'])
                : CarbonImmutable::now();

            $batch = Batch::query()->create([
                'provider_id' => $provider->getKey(),
                'batch_no' => $data['batch_no'] ?? null,
                'purchased_at' => $purchasedAt,
                'status' => BatchStatus::Confirmed,
                'notes' => $data['notes'] ?? null,
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($data['items'] as $index => $item) {
                $product = Product::query()
                    ->with('category:id,provider_id')
                    ->whereKey($item['product_id'])
                    ->where('is_active', true)
                    ->first();

                if ($product === null || $product->category?->provider_id !== $provider->getKey()) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => [
                            'The product is invalid, inactive, or does not belong to the selected provider.',
                        ],
                    ]);
                }

                $storage = Storage::query()
                    ->whereKey($item['storage_id'])
                    ->where('is_active', true)
                    ->first();

                if ($storage === null) {
                    throw ValidationException::withMessages([
                        "items.{$index}.storage_id" => ['The selected storage is invalid or inactive.'],
                    ]);
                }

                $batchItem = BatchItem::query()->create([
                    'batch_id' => $batch->getKey(),
                    'product_id' => $product->getKey(),
                    'storage_id' => $storage->getKey(),
                    'purchased_qty' => $item['qty'],
                    'unit_cost' => (string) $item['unit_cost'],
                ]);

                $this->inventoryService->createInitialBalance(
                    $batchItem,
                    $item['qty'],
                    $purchasedAt,
                    BatchItem::class,
                    $batchItem->getKey(),
                );
            }

            return $batch->load('items');
        });
    }
}
