<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class CancelReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAsAdvisor', $this->route('referral'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
