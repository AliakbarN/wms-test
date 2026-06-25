<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\ClientOrderAllocation;
use App\Models\ClientOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClientOrderAllocation> */
class ClientOrderAllocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_item_id' => ClientOrderItem::factory(),
            'batch_item_id' => function (array $attributes): int {
                $orderItem = $this->orderItem($attributes);
                $provider = $orderItem->product->category->provider;
                $batch = Batch::factory()->for($provider)->create();

                return BatchItem::factory()
                    ->for($batch)
                    ->for($orderItem->product)
                    ->create(['purchased_qty' => $orderItem->requested_qty])
                    ->getKey();
            },
            'product_id' => fn (array $attributes): int => $this->orderItem($attributes)->product_id,
            'storage_id' => fn (array $attributes): int => $this->batchItem($attributes)->storage_id,
            'qty' => fn (array $attributes): int => $this->orderItem($attributes)->requested_qty,
            'unit_cost' => fn (array $attributes): string => $this->batchItem($attributes)->unit_cost,
            'unit_sale_price' => fn (array $attributes): string => $this->orderItem($attributes)->unit_sale_price,
        ];
    }

    private function orderItem(array $attributes): ClientOrderItem
    {
        return ClientOrderItem::query()->findOrFail($attributes['order_item_id']);
    }

    private function batchItem(array $attributes): BatchItem
    {
        return BatchItem::query()->findOrFail($attributes['batch_item_id']);
    }
}
