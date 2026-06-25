<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'parent_id' => null,
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }

    public function childOf(Category $parent): static
    {
        return $this->state(fn (): array => [
            'provider_id' => $parent->provider_id,
            'parent_id' => $parent->id,
        ]);
    }
}
