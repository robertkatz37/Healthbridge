<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleTourAsAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAsAgency', $this->route('referral'));
    }

    public function rules(): array
    {
        return [
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
            'requested_time_window' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
