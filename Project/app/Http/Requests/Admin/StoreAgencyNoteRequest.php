<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgencyNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageNotes', $this->route('agency'));
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
