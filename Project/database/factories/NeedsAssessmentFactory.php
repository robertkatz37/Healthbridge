<?php

namespace Database\Factories;

use App\Models\CareSeeker;
use App\Models\NeedsAssessment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NeedsAssessment> */
class NeedsAssessmentFactory extends Factory
{
    protected $model = NeedsAssessment::class;

    public function definition(): array
    {
        return [
            'care_seeker_id' => CareSeeker::factory(),
            'status' => 'in_progress',
            'current_step' => 1,
        ];
    }
}
