<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Family> */
class FamilyFactory extends Factory
{
    protected $model = Family::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'phone' => fake()->numerify('(###) ###-####'),
            'relationship_to_seeker' => fake()->randomElement(['daughter', 'son', 'spouse', 'grandchild', 'niece', 'nephew']),
        ];
    }
}
