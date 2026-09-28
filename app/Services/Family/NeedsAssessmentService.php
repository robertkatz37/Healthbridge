<?php

namespace App\Services\Family;

use App\Models\CareSeeker;
use App\Models\NeedsAssessment;
use App\Models\NeedsAssessmentQuestion;
use Illuminate\Support\Collection;

/**
 * Drives the 6-step Needs Assessment wizard. Questions live in
 * needs_assessment_questions grouped by `section` (a data/scoring
 * grouping); this service maps several sections onto each UI wizard step
 * via STEP_SECTIONS — a deliberate, fixed 6-step consolidation of the 11
 * preference categories from SRS/Phase 9 requirements, rather than one
 * step per section (11 steps would be an unreasonably long wizard; see
 * DATABASE_DECISIONS.md for the full reasoning).
 *
 * "Autosave" is satisfied the same way as the Agency Onboarding Wizard
 * (Phase 7): each step's answers are persisted to the database on that
 * step's submission, not via per-keystroke AJAX — consistent with the
 * established platform pattern.
 */
class NeedsAssessmentService
{
    public const TOTAL_STEPS = 6;

    private const STEP_SECTIONS = [
        1 => ['care_type', 'timeline'],
        2 => ['budget', 'location'],
        3 => ['medical', 'mobility', 'memory'],
        4 => ['adls', 'behavioral'],
        5 => ['languages', 'insurance'],
        6 => [], // Review & Submit — no questions, just a summary
    ];

    public function startOrResume(CareSeeker $careSeeker): NeedsAssessment
    {
        $existing = NeedsAssessment::where('care_seeker_id', $careSeeker->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        return NeedsAssessment::create([
            'care_seeker_id' => $careSeeker->id,
            'status' => 'in_progress',
            'current_step' => 1,
        ]);
    }

    public function questionsForStep(int $step): Collection
    {
        $sections = self::STEP_SECTIONS[$step] ?? [];

        if (empty($sections)) {
            return collect();
        }

        return NeedsAssessmentQuestion::active()
            ->whereIn('section', $sections)
            ->orderBy('section_order')
            ->orderBy('sort_order')
            ->get();
    }

    public function existingAnswers(NeedsAssessment $assessment, int $step): array
    {
        $questionIds = $this->questionsForStep($step)->pluck('id');

        return $assessment->answers()
            ->whereIn('question_id', $questionIds)
            ->get()
            ->mapWithKeys(fn ($answer) => [$answer->question->code => $answer->answer_value])
            ->toArray();
    }

    /**
     * Persists this step's answers (keyed by question code) and advances
     * current_step. $answers values may be scalar (text/number/single_select)
     * or array (multi_select).
     */
    public function saveStepAnswers(NeedsAssessment $assessment, int $step, array $answers): void
    {
        $questions = $this->questionsForStep($step)->keyBy('code');

        foreach ($answers as $code => $value) {
            $question = $questions->get($code);
            if (!$question || $value === null || $value === '') {
                continue;
            }

            $assessment->answers()->updateOrCreate(
                ['question_id' => $question->id],
                ['answer_value' => $value]
            );
        }

        if ($assessment->current_step < $step + 1) {
            $assessment->update(['current_step' => min($step + 1, self::TOTAL_STEPS)]);
        }
    }

    public function resumeStep(NeedsAssessment $assessment): int
    {
        return min(max($assessment->current_step, 1), self::TOTAL_STEPS);
    }

    /**
     * Finalizes the assessment: builds a structured score_profile (answers
     * grouped by section — the raw material Phase 11's Matching Engine
     * will weight/score; no scoring algorithm is implemented here, that is
     * explicitly out of scope per PROJECT_ROADMAP.md), writes the answers
     * back to the CareSeeker's directly-editable profile columns so the
     * Profile view and the wizard never drift out of sync, and marks the
     * assessment complete.
     */
    public function complete(NeedsAssessment $assessment): void
    {
        $assessment->load('answers.question');

        $assessment->update([
            'status' => 'completed',
            'completed_at' => now(),
            'score_profile' => $this->buildScoreProfile($assessment),
        ]);

        $this->writeBackToCareSeeker($assessment);
    }

    private function buildScoreProfile(NeedsAssessment $assessment): array
    {
        $profile = [];

        foreach ($assessment->answers as $answer) {
            $section = $answer->question->section ?? 'general';
            $profile[$section][$answer->question->code] = $answer->answer_value;
        }

        return $profile;
    }

    /**
     * Maps question codes directly onto CareSeeker column names where a
     * 1:1 mapping exists. Not every question has a dedicated profile
     * column (e.g. a conditional "dementia diagnosis" follow-up) — those
     * remain captured only in needs_assessment_answers, which is fine:
     * the profile holds the durable summary a family edits directly, the
     * assessment holds the full structured detail for future scoring.
     */
    private function writeBackToCareSeeker(NeedsAssessment $assessment): void
    {
        $careSeeker = $assessment->careSeeker;
        $answers = $assessment->answers->keyBy(fn ($a) => $a->question->code);

        $updates = [];

        $direct = [
            'care_type_needed', 'move_in_timeline', 'budget_min', 'budget_max',
            'preferred_city', 'preferred_state', 'mobility', 'memory_status',
            'adl_needs', 'languages', 'insurance_provider',
        ];
        foreach ($direct as $code) {
            if ($answers->has($code)) {
                $updates[$code] = $answers->get($code)->answer_value;
            }
        }

        foreach (['has_ltc_insurance', 'is_veteran'] as $code) {
            if ($answers->has($code)) {
                $updates[$code] = $answers->get($code)->answer_value === 'yes';
            }
        }

        $medicalParts = array_filter([
            $this->flattenAnswer($answers->get('medical_conditions')?->answer_value),
            $answers->get('medical_conditions_other')?->answer_value,
        ]);
        if (!empty($medicalParts)) {
            $updates['medical_conditions'] = implode(', ', $medicalParts);
        }

        $behavioralParts = array_filter([
            $this->flattenAnswer($answers->get('behavioral_concerns')?->answer_value),
            $answers->get('behavioral_notes_other')?->answer_value,
        ]);
        if (!empty($behavioralParts)) {
            $updates['behavioral_notes'] = implode(', ', $behavioralParts);
        }

        if (!empty($updates)) {
            $careSeeker->update($updates);
        }
    }

    private function flattenAnswer(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return is_array($value) ? implode(', ', $value) : (string) $value;
    }

    /**
     * Whether this step's data has been sufficiently completed to advance
     * — currently a light check (at least one answer recorded for a
     * non-empty step); per-question "required" enforcement happens in
     * SubmitNeedsAssessmentAnswerRequest at the field level.
     */
    public function isStepComplete(NeedsAssessment $assessment, int $step): bool
    {
        $questionIds = $this->questionsForStep($step)->pluck('id');
        if ($questionIds->isEmpty()) {
            return true;
        }

        return $assessment->answers()->whereIn('question_id', $questionIds)->exists();
    }
}
