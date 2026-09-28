<?php

namespace App\Http\Requests\Advisor;

use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(LeadStatus::class)],
            'reason' => ['required_if:status,closed_lost', 'nullable', 'string', 'max:2000'],
        ];
    }
}
