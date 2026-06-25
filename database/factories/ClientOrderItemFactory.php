<?php

namespace Database\Factories;

use App\Models\ClientOrder;
use App\Models\ClientOrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClientOrderItem> */
class ClientOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => ClientOrder::factory(),
            'product_id' => Product::factory(),
            'requested_qty' => fake()->numberBetween(1, 100),
            'unit_sale_price' => fn (array $attributes): string => Product::query()->findOrFail($attributes['product_id'])->default_sale_price,
        ];
    }
}
