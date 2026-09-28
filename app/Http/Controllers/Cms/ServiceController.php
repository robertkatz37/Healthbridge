<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\City;
use App\Services\Cms\BreadcrumbService;
use App\Services\Cms\SeoMetaService;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly SeoMetaService $seo,
    ) {}

    public function index(): View
    {
        $services = AgencyCategory::active()->withCount('agencies')->get();

        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add('Services');

        return view('cms.services.index', compact('services', 'breadcrumbs'));
    }

    public function show(AgencyCategory $category): View
    {
        abort_unless($category->is_active, 404);

        $agencies = Agency::published()->where('agency_category_id', $category->id)->paginate(12);

        $cityNames = Agency::published()->where('agency_category_id', $category->id)->pluck('city')->unique();
        $nearbyCities = City::active()->whereIn('name', $cityNames)->with('state')->limit(10)->get();

        $relatedServices = AgencyCategory::active()->where('id', '!=', $category->id)->limit(5)->get();

        $guide = $category->guide;
        $faqs = $guide?->faqs()->orderBy('sort_order')->get() ?? collect();

        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add('Services', route('services.index'))->add($category->name);
        $seoTitle = $this->seo->resolveMetaTitle($guide?->seoMeta, $category->name . ' Near You');
        $seoDescription = $this->seo->resolveMetaDescription($guide?->seoMeta, 'Find trusted ' . $category->name . ' providers near you.');
        $canonicalUrl = url('/' . $category->slug);
        $robots = $this->seo->resolveRobots($guide?->seoMeta);
        $faqSchema = $this->seo->faqSchema($faqs);
        $breadcrumbSchema = $breadcrumbs->schema();

        return view('cms.services.show', compact(
            'category', 'agencies', 'nearbyCities', 'relatedServices', 'guide', 'faqs',
            'breadcrumbs', 'seoTitle', 'seoDescription', 'canonicalUrl', 'robots', 'faqSchema', 'breadcrumbSchema'
        ));
    }
}
