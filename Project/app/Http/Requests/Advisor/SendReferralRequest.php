<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class SendReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'agency_ids' => ['required', 'array', 'min:1'],
            'agency_ids.*' => ['integer', 'exists:agencies,id'],
            'priority' => ['required', 'in:low,medium,high'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
