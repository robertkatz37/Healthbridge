<?php

namespace App\Http\Requests\Admin\Cms;

use App\Models\CmsPage;
use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CmsPage::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'alpha_dash', 'unique:cms_pages,slug'],
            'body' => ['required', 'string'],
            'page_type' => ['required', 'in:home,about,contact,privacy,terms,careers,custom'],
            'template' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:draft,published,scheduled,archived'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots' => ['nullable', 'string', 'max:30'],
        ];
    }
}
