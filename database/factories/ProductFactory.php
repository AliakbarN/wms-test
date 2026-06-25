<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'sku' => fake()->unique()->bothify('SKU-########'),
            'name' => fake()->words(3, true),
            'default_sale_price' => number_format(fake()->numberBetween(0, 10_000_000) / 100, 2, '.', ''),
            'is_active' => true,
        ];
    }
}
