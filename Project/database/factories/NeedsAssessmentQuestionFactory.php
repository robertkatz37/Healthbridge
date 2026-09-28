<?php

namespace Database\Factories;

use App\Models\NeedsAssessmentQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NeedsAssessmentQuestion> */
class NeedsAssessmentQuestionFactory extends Factory
{
    protected $model = NeedsAssessmentQuestion::class;

    public function definition(): array
    {
        return [
            'code' => 'test_question_' . fake()->unique()->numberBetween(1, 999999),
            'section' => 'care_type',
            'section_order' => 1,
            'question_text' => fake()->sentence() . '?',
            'input_type' => 'text',
            'options' => null,
            'display_condition' => null,
            'weight' => 1.0,
            'sort_order' => 1,
            'is_active' => true,
        ];
    }
}
