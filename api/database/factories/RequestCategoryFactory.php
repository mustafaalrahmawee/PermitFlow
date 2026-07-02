<?php

namespace Database\Factories;

use App\Models\RequestCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestCategory>
 */
class RequestCategoryFactory extends Factory
{
    protected $model = RequestCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
