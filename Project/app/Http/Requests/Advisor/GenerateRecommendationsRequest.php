<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class GenerateRecommendationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('lead'));
    }

    public function rules(): array
    {
        return [];
    }
}
