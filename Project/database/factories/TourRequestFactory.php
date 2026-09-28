<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Family;
use App\Models\TourRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TourRequest> */
class TourRequestFactory extends Factory
{
    protected $model = TourRequest::class;

    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'agency_id' => Agency::factory(),
            'requested_date' => now()->addDays(5)->toDateString(),
            'status' => 'requested',
        ];
    }
}
