<?php

namespace Database\Factories;

use App\Models\NeedsAssessment;
use App\Models\NeedsAssessmentAnswer;
use App\Models\NeedsAssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NeedsAssessmentAnswer> */
class NeedsAssessmentAnswerFactory extends Factory
{
    protected $model = NeedsAssessmentAnswer::class;

    public function definition(): array
    {
        return [
            'needs_assessment_id' => NeedsAssessment::factory(),
            'question_id' => NeedsAssessmentQuestion::factory(),
            'answer_value' => fake()->word(),
        ];
    }
}
