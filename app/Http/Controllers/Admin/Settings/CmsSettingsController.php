<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CmsSettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function edit(Request $request): View
    {
        $this->authorize('manage-platform-settings');

        $values = $this->settings->getGroup('cms');

        return view('admin.settings.cms', compact('values'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-platform-settings');

        $request->validate([
            'blog_enabled' => ['sometimes', 'boolean'],
            'blog_comments_enabled' => ['sometimes', 'boolean'],
            'blog_posts_per_page' => ['required', 'integer', 'min:1', 'max:50'],
            'homepage_cms_page_id' => ['nullable', 'exists:cms_pages,id'],
            'sitemap_include_agencies' => ['sometimes', 'boolean'],
            'sitemap_include_blog' => ['sometimes', 'boolean'],
            'seo_default_title_suffix' => ['required', 'string', 'max:100'],
            'seo_default_meta_description' => ['required', 'string', 'max:500'],
            'seo_default_robots' => ['required', 'string', 'max:30'],
        ]);

        $this->settings->set('blog_enabled', $request->boolean('blog_enabled') ? 'true' : 'false', 'cms', 'bool');
        $this->settings->set('blog_comments_enabled', $request->boolean('blog_comments_enabled') ? 'true' : 'false', 'cms', 'bool');
        $this->settings->set('blog_posts_per_page', $request->blog_posts_per_page, 'cms', 'int');
        $this->settings->set('homepage_cms_page_id', $request->homepage_cms_page_id ?? '', 'cms', 'string');
        $this->settings->set('sitemap_include_agencies', $request->boolean('sitemap_include_agencies') ? 'true' : 'false', 'cms', 'bool');
        $this->settings->set('sitemap_include_blog', $request->boolean('sitemap_include_blog') ? 'true' : 'false', 'cms', 'bool');
        $this->settings->set('seo_default_title_suffix', $request->seo_default_title_suffix, 'cms', 'string');
        $this->settings->set('seo_default_meta_description', $request->seo_default_meta_description, 'cms', 'string');
        $this->settings->set('seo_default_robots', $request->seo_default_robots, 'cms', 'string');

        activity()->causedBy($request->user())->log('CMS settings updated');

        return back()->with('status', 'settings-updated');
    }
}
