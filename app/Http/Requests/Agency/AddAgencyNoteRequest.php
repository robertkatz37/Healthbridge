<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class AddAgencyNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAsAgency', $this->route('referral'));
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'visible_to_family' => ['sometimes', 'boolean'],
        ];
    }
}
