<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class TransitionReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAsAdvisor', $this->route('referral'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
