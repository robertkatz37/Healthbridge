<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdvisorTerritoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // scoped to the acting advisor's own record in the controller
    }

    public function rules(): array
    {
        return [
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'radius_miles' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
