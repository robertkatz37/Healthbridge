<?php

namespace App\Http\Requests\Advisor;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdvisorPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $advisor = \App\Models\Advisor::where('user_id', $this->user()->id)->first();

        return $advisor && $this->user()->can('update', $advisor);
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
