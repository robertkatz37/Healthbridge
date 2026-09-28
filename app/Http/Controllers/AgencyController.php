<?php

namespace App\Http\Controllers;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Services\Cms\BreadcrumbService;
use App\Services\Cms\SeoMetaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public-facing agency directory (search/listing/detail). Full search
 * with Meilisearch faceting arrives in Phase 18 — this is a straightforward
 * Eloquent-backed listing sufficient for the directory to function today.
 */
class AgencyController extends Controller
{
    public function __construct(
        private readonly SeoMetaService $seo,
    ) {}

    public function index(Request $request): View
    {
        $query = Agency::published()->with('category');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('code', $request->category));
        }

        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }

        $agencies = $query->orderByDesc('is_featured')->orderByDesc('review_score')->paginate(12)->withQueryString();
        $categories = AgencyCategory::active()->get();

        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add('Agencies');

        return view('agencies.index', compact('agencies', 'categories', 'breadcrumbs'));
    }

    public function show(Request $request, Agency $agency): View
    {
        abort_unless($agency->status === AgencyStatus::Published, 404);

        $agency->load(['category', 'services.catalogService', 'hours', 'coverage', 'certifications', 'media', 'pricing']);

        $reviewsQuery = $agency->reviews()->published()->with(['reply', 'categoryRatings.category', 'media']);

        $sort = $request->input('reviews_sort', 'recent');
        match ($sort) {
            'highest' => $reviewsQuery->orderByDesc('overall_rating'),
            'lowest' => $reviewsQuery->orderBy('overall_rating'),
            'helpful' => $reviewsQuery->orderByDesc('helpful_count'),
            default => $reviewsQuery->latest(),
        };

        if ($request->filled('reviews_rating')) {
            $reviewsQuery->where('overall_rating', '>=', (float) $request->reviews_rating);
        }

        $reviews = $reviewsQuery->paginate(10, ['*'], 'reviews_page')->withQueryString();

        $ratingBreakdown = $agency->reviews()->published()
            ->selectRaw('round(overall_rating) as rounded_rating, count(*) as count')
            ->groupBy('rounded_rating')->orderByDesc('rounded_rating')->pluck('count', 'rounded_rating');

        $categoryAverages = \App\Models\ReviewCategory::active()->get()->mapWithKeys(function ($category) use ($agency) {
            $avg = \App\Models\ReviewCategoryRating::where('review_category_id', $category->id)
                ->whereIn('review_id', $agency->reviews()->published()->pluck('id'))
                ->avg('rating');
            return [$category->name => $avg ? round($avg, 1) : null];
        })->filter();

        $agencyAwards = app(\App\Services\Review\ReviewAwardService::class)->awardsFor($agency);

        // SEO (Phase 15) — Agency Detail Pages get the same treatment as
        // every other public page: dynamic title/description, canonical,
        // robots, and LocalBusiness + Review/AggregateRating JSON-LD.
        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add('Agencies', route('agencies.index'))->add($agency->name);
        $seoTitle = $this->seo->resolveMetaTitle($agency->seoMeta, $agency->name . ' — ' . $agency->category->name . ' in ' . $agency->city . ', ' . $agency->state);
        $seoDescription = $this->seo->resolveMetaDescription($agency->seoMeta, $agency->description ? \Illuminate\Support\Str::limit(strip_tags($agency->description), 155) : null);
        $canonicalUrl = $agency->seoMeta?->canonical_url ?? route('agencies.show', $agency);
        $robots = $this->seo->resolveRobots($agency->seoMeta);
        $ogImage = $agency->seoMeta?->og_image ?? $agency->media->firstWhere('type.value', 'photo')?->path;
        $localBusinessSchema = $this->seo->localBusinessSchema($agency);
        $reviewSchema = $this->seo->reviewSchema($agency);
        $breadcrumbSchema = $breadcrumbs->schema();

        return view('agencies.show', compact(
            'agency', 'reviews', 'sort', 'ratingBreakdown', 'categoryAverages', 'agencyAwards',
            'breadcrumbs', 'seoTitle', 'seoDescription', 'canonicalUrl', 'robots', 'ogImage',
            'localBusinessSchema', 'reviewSchema', 'breadcrumbSchema'
        ));
    }
}
