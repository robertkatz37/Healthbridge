<?php

namespace App\Services\Review;

use App\Models\Agency;
use App\Models\ReviewCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Computes the 6 requested awards live (short cache), since no real
 * cron/scheduler infrastructure exists to run a recurring job in this
 * environment. Every award requires a minimum review count so a single
 * 5-star review can't sweep every category.
 */
class ReviewAwardService
{
    private const MIN_REVIEWS_FOR_AWARD = 3;
    private const CACHE_TTL_SECONDS = 300;

    public function currentAwards(): Collection
    {
        return Cache::remember('review_awards:current', self::CACHE_TTL_SECONDS, fn () => collect([
            'top_rated' => $this->topRated(),
            'most_reviewed' => $this->mostReviewed(),
            'family_favorite' => $this->familyFavorite(),
            'best_staff' => $this->bestCategory('staff'),
            'best_communication' => $this->bestCategory('communication'),
            'best_value' => $this->bestCategory('value'),
        ]));
    }

    public function awardsFor(Agency $agency): Collection
    {
        return $this->currentAwards()->filter(fn ($winner) => $winner && $winner->id === $agency->id)->keys();
    }

    /**
     * Returns a Collection, not a query builder — the minimum-review-
     * count filter is applied in PHP after fetching, rather than via a
     * SQL GROUP BY + HAVING on a withCount()-derived column. That
     * combination behaves differently across database engines: SQLite
     * accepts grouping by the primary key alone, but real MySQL (under
     * the default ONLY_FULL_GROUP_BY mode) rejected the exact same
     * query in production — "agencies.user_id isn't in GROUP BY" —
     * despite grouping by the primary key, which should functionally
     * determine every other column but wasn't recognized as such here.
     * Filtering in PHP instead sidesteps the engine difference entirely
     * rather than chasing another SQL-level workaround that might only
     * be safe on the one engine actually tested against.
     */
    private function eligibleAgencies(): Collection
    {
        return Agency::withCount(['reviews as published_review_count' => fn ($q) => $q->where('status', 'published')])
            ->get()
            ->filter(fn (Agency $agency) => $agency->published_review_count >= self::MIN_REVIEWS_FOR_AWARD);
    }

    private function topRated(): ?Agency
    {
        return $this->eligibleAgencies()->sortByDesc('review_score')->first();
    }

    private function mostReviewed(): ?Agency
    {
        return $this->eligibleAgencies()->sortByDesc('published_review_count')->first();
    }

    private function familyFavorite(): ?Agency
    {
        return $this->eligibleAgencies()
            ->map(function (Agency $agency) {
                $reviews = $agency->reviews()->published()->whereNotNull('would_recommend')->get();
                $agency->recommend_rate = $reviews->isEmpty() ? 0 : $reviews->where('would_recommend', true)->count() / $reviews->count();
                return $agency;
            })
            ->filter(fn (Agency $a) => $a->recommend_rate > 0)
            ->sortByDesc('recommend_rate')
            ->first();
    }

    private function bestCategory(string $categoryCode): ?Agency
    {
        $category = ReviewCategory::where('code', $categoryCode)->first();
        if (!$category) {
            return null;
        }

        return $this->eligibleAgencies()
            ->map(function (Agency $agency) use ($category) {
                $agency->category_average = \App\Models\ReviewCategoryRating::where('review_category_id', $category->id)
                    ->whereIn('review_id', $agency->reviews()->published()->pluck('id'))
                    ->avg('rating');
                return $agency;
            })
            ->filter(fn (Agency $a) => $a->category_average !== null)
            ->sortByDesc('category_average')
            ->first();
    }
}
