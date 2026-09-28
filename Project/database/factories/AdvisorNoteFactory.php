<?php

namespace Database\Factories;

use App\Models\Advisor;
use App\Models\AdvisorNote;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdvisorNote> */
class AdvisorNoteFactory extends Factory
{
    protected $model = AdvisorNote::class;

    public function definition(): array
    {
        return [
            'advisor_id' => Advisor::factory(),
            'family_id' => Family::factory(),
            'note_type' => 'general',
            'content' => fake()->sentence(),
        ];
    }
}
