<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvisorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $advisor = \App\Models\Advisor::where('user_id', $this->user()->id)->first();

        return $advisor && $this->user()->can('update', $advisor);
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:50'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'certifications' => ['nullable', 'string', 'max:255'],
            'working_hours' => ['nullable', 'array'],
            'working_hours.*.day' => ['required_with:working_hours', 'string'],
            'working_hours.*.start' => ['nullable', 'string'],
            'working_hours.*.end' => ['nullable', 'string'],
            'working_hours.*.enabled' => ['sometimes', 'boolean'],
            'is_available' => ['sometimes', 'boolean'],
            'email_signature' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
