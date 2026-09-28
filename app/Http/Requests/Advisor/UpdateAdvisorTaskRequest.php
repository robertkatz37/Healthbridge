<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdvisorTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['required', 'date'],
            'priority' => ['required', 'in:low,medium,high'],
            'lead_id' => ['nullable', 'exists:leads,id'],
        ];
    }
}
