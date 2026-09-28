<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class ShortlistMatchResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('care_seeker'));
    }

    public function rules(): array
    {
        return [];
    }
}
