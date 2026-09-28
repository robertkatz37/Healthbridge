<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('agency') ?? $this->user()->currentAgency());
    }

    public function rules(): array
    {
        $agencyId = $this->user()->currentAgency()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'agency_category_id' => ['required', 'exists:agency_categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'min_monthly_cost' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'max_monthly_cost' => ['nullable', 'numeric', 'min:0', 'max:99999.99', 'gte:min_monthly_cost'],
        ];
    }
}
