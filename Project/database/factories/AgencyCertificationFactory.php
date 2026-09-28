<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyCertification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AgencyCertification> */
class AgencyCertificationFactory extends Factory
{
    protected $model = AgencyCertification::class;

    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->randomElement(['State License', 'Joint Commission Accreditation', 'CARF Accreditation']),
            'issuing_body' => fake()->company(),
            'issued_at' => fake()->dateTimeBetween('-3 years', '-1 year'),
            'expires_at' => fake()->dateTimeBetween('now', '+2 years'),
        ];
    }
}
