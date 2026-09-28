<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdvisorTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\AdvisorTask::class);
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
