<x-admin-layout title="CMS Settings">
    @slot('breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="hb-link">Dashboard</a></li>
        <li class="breadcrumb-item active">CMS Settings</li>
    @endslot

    @if(session('status'))
        <div class="hb-alert hb-alert-success mb-4" data-auto-dismiss="5000"><i class="bi bi-check-circle me-2"></i>Settings saved.</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.cms.update') }}">
        @csrf @method('PUT')

        <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Blog</h6>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="blog_enabled" value="1" id="blogEnabled" {{ ($values['blog_enabled'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="blogEnabled">Enable Blog</label>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="blog_comments_enabled" value="1" id="commentsEnabled" {{ ($values['blog_comments_enabled'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="commentsEnabled">Enable Comments</label>
                </div>
                <label class="hb-form-label">Posts Per Page</label>
                <input type="number" name="blog_posts_per_page" class="hb-form-control" min="1" max="50" value="{{ $values['blog_posts_per_page'] ?? 12 }}">
            </div>
        </div>

        <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Homepage & Sitemap</h6>
                <label class="hb-form-label">Homepage CMS Page</label>
                <select name="homepage_cms_page_id" class="hb-form-control mb-3">
                    <option value="">— Use default welcome page —</option>
                    @foreach(\App\Models\CmsPage::orderBy('title')->get() as $page)
                        <option value="{{ $page->id }}" {{ ($values['homepage_cms_page_id'] ?? '') == $page->id ? 'selected' : '' }}>{{ $page->title }}</option>
                    @endforeach
                </select>
                <div class="form-check mb-2">
                    <input type="checkbox" class="form-check-input" name="sitemap_include_agencies" value="1" id="sitemapAgencies" {{ ($values['sitemap_include_agencies'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="sitemapAgencies">Include Agencies in Sitemap</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="sitemap_include_blog" value="1" id="sitemapBlog" {{ ($values['sitemap_include_blog'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="sitemapBlog">Include Blog Posts in Sitemap</label>
                </div>
            </div>
        </div>

        <div class="card mb-3" style="border-radius:1rem;border:none;box-shadow:0 2px 8px rgba(6,61,46,0.07);">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3" style="color:var(--hb-gray-900);">Default SEO</h6>
                <label class="hb-form-label">Title Suffix</label>
                <input type="text" name="seo_default_title_suffix" class="hb-form-control mb-3" value="{{ $values['seo_default_title_suffix'] ?? 'HealthsBridge' }}">
                <label class="hb-form-label">Default Meta Description</label>
                <textarea name="seo_default_meta_description" class="hb-form-control mb-3" rows="2">{{ $values['seo_default_meta_description'] ?? 'Find trusted senior care agencies and services near you with HealthsBridge.' }}</textarea>
                <label class="hb-form-label">Default Robots</label>
                <select name="seo_default_robots" class="hb-form-control">
                    <option value="index,follow" {{ ($values['seo_default_robots'] ?? 'index,follow') === 'index,follow' ? 'selected' : '' }}>index, follow</option>
                    <option value="noindex,follow" {{ ($values['seo_default_robots'] ?? '') === 'noindex,follow' ? 'selected' : '' }}>noindex, follow</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="border-radius:0.625rem;">Save Settings</button>
    </form>
</x-admin-layout>
