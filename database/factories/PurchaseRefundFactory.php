<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\PurchaseRefund;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PurchaseRefund> */
class PurchaseRefundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_id' => Batch::factory(),
            'provider_id' => fn (array $attributes): int => Batch::query()->findOrFail($attributes['batch_id'])->provider_id,
            'refunded_at' => now(),
            'reason' => fake()->optional()->sentence(),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
