<?php

namespace Database\Factories;

use App\Models\Advisor;
use App\Models\AdvisorTerritory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdvisorTerritory> */
class AdvisorTerritoryFactory extends Factory
{
    protected $model = AdvisorTerritory::class;

    public function definition(): array
    {
        return [
            'advisor_id' => Advisor::factory(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'radius_miles' => fake()->numberBetween(10, 50),
        ];
    }
}
