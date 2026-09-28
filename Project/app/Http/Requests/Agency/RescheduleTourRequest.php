<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleTourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tourRequest'));
    }

    public function rules(): array
    {
        return [
            'requested_date' => ['required', 'date', 'after_or_equal:today'],
            'requested_time_window' => ['nullable', 'string', 'max:100'],
        ];
    }
}
