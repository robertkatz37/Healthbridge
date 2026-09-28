<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->user()->currentAgency());
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'in:license,insurance,accreditation,other'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ];
    }
}
