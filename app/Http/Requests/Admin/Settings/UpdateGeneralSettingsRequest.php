<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-platform-settings');
    }

    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:255'],
            'support_email' => ['required', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'timezone' => ['required', 'string', 'timezone'],
            'maintenance_mode' => ['sometimes', 'boolean'],
        ];
    }
}
