<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class StoreCareSeekerDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('care_seeker'));
    }

    public function rules(): array
    {
        return [
            'document_type' => ['required', 'in:medical_record,insurance_card,poa_document,other'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ];
    }
}
