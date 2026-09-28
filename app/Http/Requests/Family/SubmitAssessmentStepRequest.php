<?php

namespace App\Http\Requests\Family;

use App\Services\Family\NeedsAssessmentService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules are built dynamically from the current step's active
 * questions (input_type drives the rule: text/number/single_select/
 * multi_select) rather than a static rules() array, since the question
 * bank is data-driven (see NeedsAssessmentService). Every question is
 * optional at the HTTP validation layer — a family can save partial
 * progress on a step without answering everything, consistent with the
 * "autosave" requirement; nothing here blocks step-to-step navigation.
 */
class SubmitAssessmentStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('care_seeker'));
    }

    public function rules(): array
    {
        $step = (int) $this->route('step');
        $questions = app(NeedsAssessmentService::class)->questionsForStep($step);

        $rules = [];

        foreach ($questions as $question) {
            $field = "answers.{$question->code}";

            $rules[$field] = match ($question->input_type) {
                'number' => ['nullable', 'numeric'],
                'text' => ['nullable', 'string', 'max:2000'],
                'single_select' => ['nullable', 'string', 'in:' . implode(',', $this->optionValues($question))],
                'multi_select' => ['nullable', 'array'],
                'scale' => ['nullable', 'integer'],
                default => ['nullable'],
            };

            if ($question->input_type === 'multi_select') {
                $rules["{$field}.*"] = ['string', 'in:' . implode(',', $this->optionValues($question))];
            }
        }

        return $rules;
    }

    private function optionValues($question): array
    {
        $options = $question->options ?? [];

        // Options are stored as [{value, label}, ...] or a flat value list —
        // support both to keep the seeder free to use whichever is clearer
        // per question.
        return collect($options)->map(fn ($opt) => is_array($opt) ? ($opt['value'] ?? $opt) : $opt)->toArray();
    }
}
