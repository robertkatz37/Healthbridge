<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class AssignLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'advisor_id' => ['required', 'exists:advisors,id'],
        ];
    }
}
