<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Review::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'min:20', 'max:5000'],
            'category_ratings' => ['required', 'array', 'min:1'],
            'category_ratings.*' => ['integer', 'min:1', 'max:5'],
            'would_recommend' => ['nullable', 'boolean'],
            'is_anonymous' => ['sometimes', 'boolean'],
            'media' => ['nullable', 'array', 'max:6'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov', 'max:20480'],
        ];
    }
}
