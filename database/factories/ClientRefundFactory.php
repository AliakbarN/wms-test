<?php

namespace Database\Factories;

use App\Models\ClientOrder;
use App\Models\ClientRefund;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ClientRefund> */
class ClientRefundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => ClientOrder::factory(),
            'client_id' => fn (array $attributes): int => ClientOrder::query()->findOrFail($attributes['order_id'])->client_id,
            'refunded_at' => now(),
            'reason' => fake()->optional()->sentence(),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
