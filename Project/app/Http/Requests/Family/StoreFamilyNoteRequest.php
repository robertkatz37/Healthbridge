<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class StoreFamilyNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\FamilyNote::class);
    }

    public function rules(): array
    {
        return [
            'care_seeker_id' => ['nullable', 'exists:care_seekers,id'],
            'note' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }
}
