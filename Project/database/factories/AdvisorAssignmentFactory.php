<?php

namespace Database\Factories;

use App\Models\Advisor;
use App\Models\AdvisorAssignment;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdvisorAssignment> */
class AdvisorAssignmentFactory extends Factory
{
    protected $model = AdvisorAssignment::class;

    public function definition(): array
    {
        return [
            'advisor_id' => Advisor::factory(),
            'family_id' => Family::factory(),
            'assigned_at' => now(),
        ];
    }
}
