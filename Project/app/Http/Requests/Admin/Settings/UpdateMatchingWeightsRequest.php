<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchingWeightsRequest extends FormRequest
{
    private const FACTOR_KEYS = [
        'care_type', 'location', 'coverage_area', 'distance', 'budget', 'services_offered',
        'languages', 'insurance_accepted', 'medicaid_medicare', 'specialty_care', 'memory_care',
        'mobility', 'availability', 'gender_preference', 'veteran_benefits', 'religious_preference',
        'pet_friendly', 'accessibility', 'review_rating', 'agency_quality', 'verification_status',
    ];

    public function authorize(): bool
    {
        return $this->user()->can('manage-platform-settings');
    }

    public function rules(): array
    {
        $rules = [
            'featured_boost' => ['required', 'integer', 'min:0', 'max:20'],
            'max_distance_miles' => ['required', 'integer', 'min:1', 'max:1000'],
            'min_score_threshold' => ['required', 'integer', 'min:0', 'max:100'],
        ];

        foreach (self::FACTOR_KEYS as $key) {
            $rules["weights.{$key}"] = ['required', 'integer', 'min:0', 'max:100'];
        }

        return $rules;
    }
}
