<?php

namespace Database\Factories;

use App\Models\BatchItem;
use App\Models\PurchaseRefund;
use App\Models\PurchaseRefundItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PurchaseRefundItem> */
class PurchaseRefundItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'purchase_refund_id' => PurchaseRefund::factory(),
            'batch_item_id' => function (array $attributes): int {
                $refund = PurchaseRefund::query()->findOrFail($attributes['purchase_refund_id']);

                return BatchItem::factory()->for($refund->batch)->create()->getKey();
            },
            'product_id' => fn (array $attributes): int => $this->batchItem($attributes)->product_id,
            'storage_id' => fn (array $attributes): int => $this->batchItem($attributes)->storage_id,
            'qty' => 1,
            'unit_refund_cost' => fn (array $attributes): string => $this->batchItem($attributes)->unit_cost,
        ];
    }

    private function batchItem(array $attributes): BatchItem
    {
        return BatchItem::query()->findOrFail($attributes['batch_item_id']);
    }
}
