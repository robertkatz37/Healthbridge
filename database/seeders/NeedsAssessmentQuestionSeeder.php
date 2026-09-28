<?php

namespace Database\Seeders;

use App\Models\NeedsAssessmentQuestion;
use Illuminate\Database\Seeder;

/**
 * Seeds the question bank consumed by NeedsAssessmentService. Question
 * `code` values are deliberately chosen to match CareSeeker column names
 * where a direct write-back mapping exists (see
 * NeedsAssessmentService::writeBackToCareSeeker()) — this is what lets
 * the wizard and the directly-editable Care Seeker profile stay in sync
 * without a separate mapping table. Grouped by `section`, which
 * NeedsAssessmentService::STEP_SECTIONS consolidates into 6 UI wizard
 * steps. Scoped to the ~17 questions needed to cover every preference
 * category from PROJECT_ROADMAP.md Phase 9 — schema supports scaling to
 * the SRS's 100+ question target in a later phase without any structural
 * change.
 */
class NeedsAssessmentQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // ─── Section: care_type ─────────────────────────────────────────
            [
                'code' => 'care_type_needed', 'section' => 'care_type', 'section_order' => 1,
                'question_text' => 'What type of care are you looking for?',
                'input_type' => 'single_select', 'weight' => 3.0, 'sort_order' => 1,
                'options' => [
                    ['value' => 'independent_living', 'label' => 'Independent Living'],
                    ['value' => 'assisted_living', 'label' => 'Assisted Living'],
                    ['value' => 'memory_care', 'label' => 'Memory Care'],
                    ['value' => 'nursing_home', 'label' => 'Nursing Home'],
                    ['value' => 'home_care', 'label' => 'Home Care'],
                    ['value' => 'hospice', 'label' => 'Hospice'],
                ],
            ],
            // ─── Section: timeline ──────────────────────────────────────────
            [
                'code' => 'move_in_timeline', 'section' => 'timeline', 'section_order' => 2,
                'question_text' => 'When are you looking to move?',
                'input_type' => 'single_select', 'weight' => 2.0, 'sort_order' => 1,
                'options' => [
                    ['value' => 'immediately', 'label' => 'Immediately'],
                    ['value' => 'within_30_days', 'label' => 'Within 30 days'],
                    ['value' => 'within_1_3_months', 'label' => '1–3 months'],
                    ['value' => 'within_3_6_months', 'label' => '3–6 months'],
                    ['value' => 'just_researching', 'label' => 'Just researching'],
                ],
            ],
            // ─── Section: budget ────────────────────────────────────────────
            [
                'code' => 'budget_min', 'section' => 'budget', 'section_order' => 1,
                'question_text' => 'What is your minimum monthly budget?',
                'input_type' => 'number', 'weight' => 2.5, 'sort_order' => 1, 'options' => null,
            ],
            [
                'code' => 'budget_max', 'section' => 'budget', 'section_order' => 1,
                'question_text' => 'What is your maximum monthly budget?',
                'input_type' => 'number', 'weight' => 2.5, 'sort_order' => 2, 'options' => null,
            ],
            // ─── Section: location ──────────────────────────────────────────
            [
                'code' => 'preferred_city', 'section' => 'location', 'section_order' => 2,
                'question_text' => 'Preferred city',
                'input_type' => 'text', 'weight' => 1.5, 'sort_order' => 1, 'options' => null,
            ],
            [
                'code' => 'preferred_state', 'section' => 'location', 'section_order' => 2,
                'question_text' => 'Preferred state',
                'input_type' => 'text', 'weight' => 1.5, 'sort_order' => 2, 'options' => null,
            ],
            // ─── Section: medical ───────────────────────────────────────────
            [
                'code' => 'medical_conditions', 'section' => 'medical', 'section_order' => 1,
                'question_text' => 'Does your loved one have any of the following medical conditions?',
                'input_type' => 'multi_select', 'weight' => 2.0, 'sort_order' => 1,
                'options' => [
                    ['value' => 'diabetes', 'label' => 'Diabetes'],
                    ['value' => 'heart_disease', 'label' => 'Heart Disease'],
                    ['value' => 'copd', 'label' => 'COPD'],
                    ['value' => 'parkinsons', 'label' => "Parkinson's"],
                    ['value' => 'stroke_recovery', 'label' => 'Stroke Recovery'],
                    ['value' => 'kidney_disease', 'label' => 'Kidney Disease'],
                ],
            ],
            [
                'code' => 'medical_conditions_other', 'section' => 'medical', 'section_order' => 1,
                'question_text' => 'Any other medical conditions we should know about?',
                'input_type' => 'text', 'weight' => 1.0, 'sort_order' => 2, 'options' => null,
            ],
            // ─── Section: mobility ──────────────────────────────────────────
            [
                'code' => 'mobility', 'section' => 'mobility', 'section_order' => 2,
                'question_text' => 'What is their mobility level?',
                'input_type' => 'single_select', 'weight' => 2.5, 'sort_order' => 1,
                'options' => [
                    ['value' => 'independent', 'label' => 'Fully Independent'],
                    ['value' => 'cane_walker', 'label' => 'Uses Cane/Walker'],
                    ['value' => 'wheelchair', 'label' => 'Wheelchair'],
                    ['value' => 'bedbound', 'label' => 'Bedbound'],
                ],
            ],
            // ─── Section: memory ────────────────────────────────────────────
            [
                'code' => 'memory_status', 'section' => 'memory', 'section_order' => 3,
                'question_text' => 'Do they have any memory-related conditions?',
                'input_type' => 'single_select', 'weight' => 3.0, 'sort_order' => 1,
                'options' => [
                    ['value' => 'none', 'label' => 'None'],
                    ['value' => 'mild', 'label' => 'Mild'],
                    ['value' => 'moderate', 'label' => 'Moderate'],
                    ['value' => 'severe', 'label' => 'Severe'],
                ],
            ],
            [
                'code' => 'dementia_diagnosis', 'section' => 'memory', 'section_order' => 3,
                'question_text' => 'Have they been diagnosed with Alzheimer\'s or another form of dementia?',
                'input_type' => 'single_select', 'weight' => 1.5, 'sort_order' => 2,
                'options' => [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No']],
                'display_condition' => ['question' => 'memory_status', 'operator' => '!=', 'value' => 'none'],
            ],
            // ─── Section: adls ──────────────────────────────────────────────
            [
                'code' => 'adl_needs', 'section' => 'adls', 'section_order' => 1,
                'question_text' => 'Which daily activities do they need help with?',
                'input_type' => 'multi_select', 'weight' => 2.5, 'sort_order' => 1,
                'options' => [
                    ['value' => 'bathing', 'label' => 'Bathing'],
                    ['value' => 'dressing', 'label' => 'Dressing'],
                    ['value' => 'toileting', 'label' => 'Toileting'],
                    ['value' => 'transferring', 'label' => 'Transferring'],
                    ['value' => 'eating', 'label' => 'Eating'],
                    ['value' => 'continence', 'label' => 'Continence'],
                    ['value' => 'medication_management', 'label' => 'Medication Management'],
                ],
            ],
            // ─── Section: behavioral ────────────────────────────────────────
            [
                'code' => 'behavioral_concerns', 'section' => 'behavioral', 'section_order' => 2,
                'question_text' => 'Are there any behavioral concerns we should know about?',
                'input_type' => 'multi_select', 'weight' => 1.5, 'sort_order' => 1,
                'options' => [
                    ['value' => 'wandering', 'label' => 'Wandering'],
                    ['value' => 'aggression', 'label' => 'Aggression'],
                    ['value' => 'sundowning', 'label' => 'Sundowning'],
                    ['value' => 'none', 'label' => 'None'],
                ],
            ],
            [
                'code' => 'behavioral_notes_other', 'section' => 'behavioral', 'section_order' => 2,
                'question_text' => 'Anything else about their behavior or temperament?',
                'input_type' => 'text', 'weight' => 0.5, 'sort_order' => 2, 'options' => null,
            ],
            // ─── Section: languages ─────────────────────────────────────────
            [
                'code' => 'languages', 'section' => 'languages', 'section_order' => 1,
                'question_text' => 'What languages does your loved one speak?',
                'input_type' => 'multi_select', 'weight' => 1.0, 'sort_order' => 1,
                'options' => [
                    ['value' => 'English', 'label' => 'English'],
                    ['value' => 'Spanish', 'label' => 'Spanish'],
                    ['value' => 'Mandarin', 'label' => 'Mandarin'],
                    ['value' => 'Vietnamese', 'label' => 'Vietnamese'],
                    ['value' => 'Other', 'label' => 'Other'],
                ],
            ],
            // ─── Section: insurance ─────────────────────────────────────────
            [
                'code' => 'has_ltc_insurance', 'section' => 'insurance', 'section_order' => 2,
                'question_text' => 'Do they have long-term care insurance?',
                'input_type' => 'single_select', 'weight' => 1.5, 'sort_order' => 1,
                'options' => [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No']],
            ],
            [
                'code' => 'insurance_provider', 'section' => 'insurance', 'section_order' => 2,
                'question_text' => 'Which insurance provider?',
                'input_type' => 'text', 'weight' => 0.5, 'sort_order' => 2,
                'options' => null,
                'display_condition' => ['question' => 'has_ltc_insurance', 'operator' => '=', 'value' => 'yes'],
            ],
            [
                'code' => 'is_veteran', 'section' => 'insurance', 'section_order' => 2,
                'question_text' => 'Is your loved one a veteran or a veteran\'s spouse?',
                'input_type' => 'single_select', 'weight' => 1.5, 'sort_order' => 3,
                'options' => [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No']],
            ],
        ];

        foreach ($questions as $q) {
            NeedsAssessmentQuestion::updateOrCreate(
                ['code' => $q['code']],
                array_merge(['is_active' => true, 'display_condition' => null], $q)
            );
        }
    }
}
