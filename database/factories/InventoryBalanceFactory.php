<?php

namespace Database\Factories;

use App\Models\BatchItem;
use App\Models\InventoryBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InventoryBalance> */
class InventoryBalanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_item_id' => BatchItem::factory(),
            'product_id' => fn (array $attributes): int => $this->batchItem($attributes)->product_id,
            'storage_id' => fn (array $attributes): int => $this->batchItem($attributes)->storage_id,
            'qty_available' => fn (array $attributes): int => $this->batchItem($attributes)->purchased_qty,
        ];
    }

    private function batchItem(array $attributes): BatchItem
    {
        return BatchItem::query()->findOrFail($attributes['batch_item_id']);
    }
}
