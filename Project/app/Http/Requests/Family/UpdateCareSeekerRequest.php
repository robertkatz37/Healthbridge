<?php

namespace App\Http\Requests\Family;

use App\Enums\CareType;
use App\Enums\MemoryStatus;
use App\Enums\MobilityLevel;
use App\Enums\MoveInTimeline;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateCareSeekerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('care_seeker'));
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'age' => ['nullable', 'integer', 'min:0', 'max:130'],
            'gender' => ['nullable', 'string', 'max:30'],
            'medical_conditions' => ['nullable', 'string', 'max:2000'],
            'memory_status' => ['nullable', new Enum(MemoryStatus::class)],
            'mobility' => ['nullable', new Enum(MobilityLevel::class)],
            'budget_min' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'max:99999.99', 'gte:budget_min'],
            'is_veteran' => ['sometimes', 'boolean'],
            'has_ltc_insurance' => ['sometimes', 'boolean'],
            'insurance_provider' => ['nullable', 'string', 'max:255'],
            'move_in_timeline' => ['nullable', new Enum(MoveInTimeline::class)],
            'preferred_city' => ['nullable', 'string', 'max:100'],
            'preferred_state' => ['nullable', 'string', 'max:100'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:50'],
            'care_type_needed' => ['nullable', new Enum(CareType::class)],
            'behavioral_notes' => ['nullable', 'string', 'max:2000'],
            'adl_needs' => ['nullable', 'array'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
