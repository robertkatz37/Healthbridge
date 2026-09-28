<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'code' => 'plan_'.fake()->unique()->numberBetween(1, 999999),
            'name' => fake()->randomElement(['Free', 'Premium', 'Professional', 'Enterprise']),
            'price_monthly' => fake()->randomElement([0, 99, 249, 599]),
            'price_yearly' => fake()->randomElement([0, 990, 2490, 5990]),
            'is_active' => true,
        ];
    }
}
