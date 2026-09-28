<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyMedia> */
class AgencyMediaFactory extends Factory
{
    protected $model = AgencyMedia::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'type' => 'photo',
            'path' => 'agencies/test/media/' . fake()->uuid() . '.jpg',
            'caption' => fake()->sentence(4),
            'sort_order' => 0,
        ];
    }
}
