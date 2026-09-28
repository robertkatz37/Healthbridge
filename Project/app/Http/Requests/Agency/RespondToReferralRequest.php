<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class RespondToReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAsAgency', $this->route('referral'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
