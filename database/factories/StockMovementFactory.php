<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\BatchItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockMovement> */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_item_id' => BatchItem::factory(),
            'product_id' => fn (array $attributes): int => $this->batchItem($attributes)->product_id,
            'storage_id' => fn (array $attributes): int => $this->batchItem($attributes)->storage_id,
            'movement_type' => StockMovementType::PurchaseIn,
            'qty_delta' => fn (array $attributes): int => $this->batchItem($attributes)->purchased_qty,
            'occurred_at' => now(),
            'source_type' => BatchItem::class,
            'source_id' => fn (array $attributes): int => $attributes['batch_item_id'],
        ];
    }

    private function batchItem(array $attributes): BatchItem
    {
        return BatchItem::query()->findOrFail($attributes['batch_item_id']);
    }
}
