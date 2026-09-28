<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'in:spam,offensive,fake,harassment,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
