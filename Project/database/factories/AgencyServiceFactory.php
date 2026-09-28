<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyService;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyService> */
class AgencyServiceFactory extends Factory
{
    protected $model = AgencyService::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->randomElement(['Personal Care', 'Medication Management', 'Housekeeping', 'Meal Preparation', '24-Hour Supervision']),
            'description' => fake()->sentence(),
            'price_from' => fake()->numberBetween(800, 4000),
            'is_active' => true,
        ];
    }
}
