<?php

namespace Database\Factories;

use App\Models\Advisor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Advisor> */
class AdvisorFactory extends Factory
{
    protected $model = Advisor::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'territory' => fake()->state(),
            'license_number' => fake()->optional()->bothify('LIC-####??'),
            'is_active' => true,
        ];
    }
}
