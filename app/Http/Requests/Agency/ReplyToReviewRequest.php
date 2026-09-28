<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class ReplyToReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reply', $this->route('review'));
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:3000'],
        ];
    }
}
