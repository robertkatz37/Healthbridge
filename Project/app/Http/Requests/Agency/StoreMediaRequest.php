<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->user()->currentAgency());
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:photo,video,virtual_tour'],
            'caption' => ['nullable', 'string', 'max:255'],
            'file' => [
                'required_if:type,photo',
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
            'url' => ['required_if:type,video,virtual_tour', 'nullable', 'url', 'max:500'],
        ];
    }
}
