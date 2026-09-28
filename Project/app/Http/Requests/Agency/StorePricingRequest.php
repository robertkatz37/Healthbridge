<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class StorePricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->user()->currentAgency());
    }

    public function rules(): array
    {
        return [
            'room_type' => ['required', 'string', 'max:100'],
            'care_level' => ['nullable', 'string', 'max:100'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }
}
