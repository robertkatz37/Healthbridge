<?php

namespace App\Services\Cms;

use App\Models\Agency;
use App\Models\BlogPost;
use App\Models\SeoMeta;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SeoMetaService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function upsertFor(Model $model, array $data): SeoMeta
    {
        return $model->seoMeta()->updateOrCreate([], $data);
    }

    public function resolveMetaTitle(?SeoMeta $seoMeta, string $pageTitle): string
    {
        if ($seoMeta?->meta_title) {
            return $seoMeta->meta_title;
        }

        $suffix = $this->settings->get('seo_default_title_suffix', 'HealthsBridge');

        return $pageTitle . ' | ' . $suffix;
    }

    public function resolveMetaDescription(?SeoMeta $seoMeta, ?string $fallback = null): string
    {
        return $seoMeta?->meta_description
            ?? $fallback
            ?? $this->settings->get('seo_default_meta_description', 'Find trusted senior care agencies and services near you with HealthsBridge.');
    }

    public function resolveRobots(?SeoMeta $seoMeta): string
    {
        return $seoMeta?->robots ?? $this->settings->get('seo_default_robots', 'index,follow');
    }

    public function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->settings->get('platform_name', 'HealthsBridge'),
            'url' => url('/'),
            'logo' => $this->settings->get('branding_logo_url', url('/logo.png')),
            'sameAs' => array_filter([
                $this->settings->get('social_facebook_url'),
                $this->settings->get('social_twitter_url'),
                $this->settings->get('social_linkedin_url'),
            ]),
        ];
    }

    public function localBusinessSchema(Agency $agency): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $agency->name,
            'description' => $agency->description,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $agency->address,
                'addressLocality' => $agency->city,
                'addressRegion' => $agency->state,
            ],
            'telephone' => $agency->phone,
            'url' => route('agencies.show', $agency),
            'aggregateRating' => $agency->review_score ? [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $agency->review_score,
                'reviewCount' => (string) $agency->reviews()->published()->count(),
            ] : null,
        ]);
    }

    public function articleSchema(BlogPost $post): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'image' => $post->featured_image ? asset('storage/' . $post->featured_image) : null,
            'author' => [
                '@type' => 'Person',
                'name' => $post->author->name,
            ],
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
        ]);
    }

    public function faqSchema(Collection $faqs): ?array
    {
        if ($faqs->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->answer,
                ],
            ])->values()->all(),
        ];
    }

    public function reviewSchema(Agency $agency): ?array
    {
        $reviews = $agency->reviews()->published()->with('categoryRatings')->latest()->limit(10)->get();
        if ($reviews->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $agency->name,
            'review' => $reviews->map(fn ($review) => [
                '@type' => 'Review',
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => (string) $review->overall_rating,
                    'bestRating' => '5',
                ],
                'author' => ['@type' => 'Person', 'name' => $review->display_name],
                'reviewBody' => $review->body,
            ])->values()->all(),
        ];
    }
}
