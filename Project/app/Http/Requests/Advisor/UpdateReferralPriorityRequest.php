<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReferralPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAsAdvisor', $this->route('referral'));
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', 'in:low,medium,high'],
        ];
    }
}
