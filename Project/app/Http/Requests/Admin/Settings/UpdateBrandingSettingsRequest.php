<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-platform-settings');
    }

    public function rules(): array
    {
        return [
            'branding_company_name' => ['required', 'string', 'max:255'],
            'branding_from_name' => ['required', 'string', 'max:255'],
            'branding_from_email' => ['required', 'email', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ];
    }
}
