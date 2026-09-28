<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyCoverage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyCoverage> */
class AgencyCoverageFactory extends Factory
{
    protected $model = AgencyCoverage::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'radius_miles' => fake()->numberBetween(10, 50),
        ];
    }
}
