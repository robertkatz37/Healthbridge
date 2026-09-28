<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyHour> */
class AgencyHourFactory extends Factory
{
    protected $model = AgencyHour::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'open_time' => '09:00',
            'close_time' => '17:00',
            'is_closed' => false,
        ];
    }
}
