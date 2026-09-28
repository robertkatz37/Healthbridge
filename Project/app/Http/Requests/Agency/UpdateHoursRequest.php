<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles a full weekly hours grid submission (7 days at once) rather than
 * one row per request — matches the "Business Hours" UI archetype of a
 * single weekly editor rather than 7 separate CRUD forms.
 */
class UpdateHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->user()->currentAgency());
    }

    public function rules(): array
    {
        return [
            'days' => ['required', 'array', 'size:7'],
            'days.*.is_closed' => ['sometimes', 'boolean'],
            'days.*.open_time' => ['nullable', 'date_format:H:i'],
            'days.*.close_time' => ['nullable', 'date_format:H:i', 'after:days.*.open_time'],
        ];
    }
}
