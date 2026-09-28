<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\Cms\BreadcrumbService;
use App\Services\Cms\SeoMetaService;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function __construct(
        private readonly SeoMetaService $seo,
    ) {}

    public function home(): View
    {
        $page = CmsPage::where('page_type', 'home')->first();

        if (!$page || !$page->isVisible()) {
            return view('welcome');
        }

        $page->load(['activeSections', 'faqs' => fn ($q) => $q->orderBy('sort_order'), 'seoMeta']);

        $breadcrumbs = BreadcrumbService::make()->add('Home');

        return view('cms.pages.show', $this->seoViewData($page, $breadcrumbs));
    }

    public function show(string $slug): View
    {
        $page = CmsPage::where('slug', $slug)->firstOrFail();
        abort_unless($page->isVisible(), 404);

        $page->load(['activeSections', 'faqs' => fn ($q) => $q->orderBy('sort_order'), 'seoMeta']);

        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add($page->title);

        return view('cms.pages.show', $this->seoViewData($page, $breadcrumbs));
    }

    /**
     * Every CMS page — not just Location/Service/Agency pages — gets
     * the same full SEO treatment: canonical, robots, breadcrumb
     * JSON-LD, and FAQ JSON-LD when the page has any FAQs attached.
     */
    private function seoViewData(CmsPage $page, BreadcrumbService $breadcrumbs): array
    {
        $seoTitle = $this->seo->resolveMetaTitle($page->seoMeta, $page->title);
        $seoDescription = $this->seo->resolveMetaDescription($page->seoMeta);
        $canonicalUrl = $page->seoMeta?->canonical_url ?? url($page->page_type === 'home' ? '/' : '/' . $page->slug);
        $robots = $this->seo->resolveRobots($page->seoMeta);
        $faqSchema = $this->seo->faqSchema($page->faqs);
        $breadcrumbSchema = $breadcrumbs->schema();

        return compact('page', 'breadcrumbs', 'seoTitle', 'seoDescription', 'canonicalUrl', 'robots', 'faqSchema', 'breadcrumbSchema');
    }
}
