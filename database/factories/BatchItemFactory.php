<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BatchItem> */
class BatchItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'product_id' => function (array $attributes): int {
                $batch = Batch::query()->findOrFail($attributes['batch_id']);

                return Product::factory()
                    ->for(Category::factory()->for($batch->provider))
                    ->create()
                    ->getKey();
            },
            'storage_id' => Storage::factory(),
            'purchased_qty' => fake()->numberBetween(1, 1000),
            'unit_cost' => number_format(fake()->numberBetween(0, 10_000_000) / 100, 2, '.', ''),
        ];
    }
}
