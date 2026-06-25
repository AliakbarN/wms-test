<?php

namespace Database\Factories;

use App\Enums\ClientOrderStatus;
use App\Models\Client;
use App\Models\ClientOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ClientOrder> */
class ClientOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_no' => fake()->unique()->bothify('ORD-########'),
            'client_id' => Client::factory(),
            'ordered_at' => now(),
            'status' => ClientOrderStatus::Confirmed,
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
