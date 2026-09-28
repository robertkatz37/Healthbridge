<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\City;
use App\Models\State;
use App\Services\Cms\BreadcrumbService;
use App\Services\Cms\SeoMetaService;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        private readonly SeoMetaService $seo,
    ) {}

    public function state(State $state): View
    {
        $agencies = Agency::published()->where('state', $state->code)->with('category')->paginate(12);
        abort_if($agencies->total() === 0 && $state->guide?->status !== 'published', 404);

        $agencyCityNames = Agency::published()->where('state', $state->code)->pluck('city')->unique();
        $cities = City::active()->where('state_id', $state->id)->whereIn('name', $agencyCityNames)->orderBy('name')->get();

        $relatedServices = AgencyCategory::active()
            ->whereHas('agencies', fn ($q) => $q->published()->where('state', $state->code))
            ->get();

        $guide = $state->guide;
        $faqs = $guide?->faqs()->orderBy('sort_order')->get() ?? collect();

        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add('Locations', route('locations.index'))->add($state->name);
        $seoTitle = $this->seo->resolveMetaTitle($guide?->seoMeta, 'Senior Care Agencies in ' . $state->name);
        $seoDescription = $this->seo->resolveMetaDescription($guide?->seoMeta, 'Browse verified senior care agencies across ' . $state->name . '.');
        $canonicalUrl = url('/' . $state->slug);
        $robots = $this->seo->resolveRobots($guide?->seoMeta);
        $faqSchema = $this->seo->faqSchema($faqs);
        $breadcrumbSchema = $breadcrumbs->schema();

        return view('cms.locations.state', compact(
            'state', 'agencies', 'cities', 'relatedServices', 'guide', 'faqs',
            'breadcrumbs', 'seoTitle', 'seoDescription', 'canonicalUrl', 'robots', 'faqSchema', 'breadcrumbSchema'
        ));
    }

    public function city(State $state, string $citySlug): View
    {
        $city = City::where('state_id', $state->id)->active()->get()
            ->first(fn (City $c) => $c->clean_slug === $citySlug);
        abort_if(!$city, 404);

        $agencies = Agency::published()->where('state', $state->code)->where('city', $city->name)->with('category')->paginate(12);

        $nearbyCities = City::active()->where('state_id', $state->id)->where('id', '!=', $city->id)->orderBy('name')->limit(6)->get();

        $relatedServices = AgencyCategory::active()
            ->whereHas('agencies', fn ($q) => $q->published()->where('state', $state->code)->where('city', $city->name))
            ->get();

        $guide = $city->guide;
        $faqs = $guide?->faqs()->orderBy('sort_order')->get() ?? collect();

        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add('Locations', route('locations.index'))
            ->add($state->name, url('/' . $state->slug))->add($city->name);
        $seoTitle = $this->seo->resolveMetaTitle($guide?->seoMeta, 'Senior Care Agencies in ' . $city->name . ', ' . $state->code);
        $seoDescription = $this->seo->resolveMetaDescription($guide?->seoMeta, 'Browse verified senior care agencies in ' . $city->name . ', ' . $state->name . '.');
        $canonicalUrl = url('/' . $state->slug . '/' . $city->clean_slug);
        $robots = $this->seo->resolveRobots($guide?->seoMeta);
        $faqSchema = $this->seo->faqSchema($faqs);
        $breadcrumbSchema = $breadcrumbs->schema();

        return view('cms.locations.city', compact(
            'state', 'city', 'agencies', 'nearbyCities', 'relatedServices', 'guide', 'faqs',
            'breadcrumbs', 'seoTitle', 'seoDescription', 'canonicalUrl', 'robots', 'faqSchema', 'breadcrumbSchema'
        ));
    }

    public function index(): View
    {
        $states = State::active()->withCount(['cities'])->orderBy('name')->get();

        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add('Locations');

        return view('cms.locations.index', compact('states', 'breadcrumbs'));
    }
}
