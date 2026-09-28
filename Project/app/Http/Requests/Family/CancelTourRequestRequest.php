<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class CancelTourRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('tourRequest'));
    }

    public function rules(): array
    {
        return [];
    }
}
