<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class AddReferralNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAsAdvisor', $this->route('referral'));
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'visible_to_agency' => ['sometimes', 'boolean'],
            'visible_to_family' => ['sometimes', 'boolean'],
        ];
    }
}
