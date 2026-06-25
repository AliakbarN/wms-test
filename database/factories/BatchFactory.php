<?php

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Batch> */
class BatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'batch_no' => fake()->unique()->bothify('BAT-########'),
            'purchased_at' => now(),
            'status' => BatchStatus::Confirmed,
            'notes' => fake()->optional()->sentence(),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
