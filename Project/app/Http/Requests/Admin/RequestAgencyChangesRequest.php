<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RequestAgencyChangesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('requestChanges', $this->route('agency'));
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
