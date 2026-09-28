<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyPricing;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyPricing> */
class AgencyPricingFactory extends Factory
{
    protected $model = AgencyPricing::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'room_type' => fake()->randomElement(['Studio', 'Shared Room', 'One Bedroom']),
            'care_level' => fake()->randomElement(['Basic Care', 'Enhanced Care', 'Memory Care']),
            'monthly_price' => fake()->numberBetween(2500, 7000),
        ];
    }
}
