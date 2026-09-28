<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\FamilyNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FamilyNote> */
class FamilyNoteFactory extends Factory
{
    protected $model = FamilyNote::class;

    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'care_seeker_id' => null,
            'note' => fake()->sentence(10),
        ];
    }
}
