<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', \App\Models\Lead::class);
    }

    public function rules(): array
    {
        return [
            'family_id' => ['required', 'exists:families,id'],
            'care_seeker_id' => ['nullable', 'exists:care_seekers,id'],
            'source' => ['required', 'in:needs_assessment,manual,agency_referral,website_inquiry'],
        ];
    }
}
