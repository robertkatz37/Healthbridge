<?php

namespace App\Services\Matching;

use App\Models\CareSeeker;
use App\Models\MatchResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The facade controllers actually call — decides WHETHER to invoke
 * MatchingEngineService (staleness/regeneration) and shapes results for
 * presentation (sort/filter), with a short-lived read cache on top of
 * the already-persisted match_results table. MatchingEngineService
 * itself stays pure computation with no caching or "should I run"
 * decisions, kept in this separate class per Phase 12's explicit
 * MatchingEngineService / AgencyRecommendationService split.
 */
class AgencyRecommendationService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly MatchingEngineService $engine,
    ) {}

    /**
     * Returns persisted recommendations, regenerating first if none
     * exist yet, the underlying assessment is newer than the last
     * generation, or the caller explicitly forces it (e.g. an advisor
     * clicking "Regenerate").
     */
    public function getRecommendations(CareSeeker $careSeeker, bool $forceRegenerate = false): Collection
    {
        $assessment = $careSeeker->latestCompletedAssessment();
        if (!$assessment) {
            return new Collection();
        }

        if ($forceRegenerate || $this->isStale($assessment)) {
            $this->engine->generateRecommendations($careSeeker);
            Cache::forget($this->cacheKey($careSeeker));
        }

        return Cache::remember(
            $this->cacheKey($careSeeker),
            self::CACHE_TTL_SECONDS,
            fn () => $assessment->matchResults()
                ->with(['agency.category', 'agency.services', 'agency.certifications'])
                ->ranked()
                ->get()
        );
    }

    /**
     * What a family should see: hides anything an advisor has
     * suppressed, and (unless includeBelowThreshold) hides scores below
     * the configured floor — computed and stored either way, for advisor/
     * audit visibility, just not surfaced to the family by default.
     */
    public function forFamily(CareSeeker $careSeeker, bool $includeBelowThreshold = false): Collection
    {
        $results = $this->getRecommendations($careSeeker)->where('is_hidden_by_advisor', false);

        if (!$includeBelowThreshold) {
            $minScore = app(MatchingRuleEngine::class)->minScoreThreshold();
            $results = $results->filter(fn (MatchResult $r) => (float) $r->compatibility_score >= $minScore);
        }

        return $results->values();
    }

    /**
     * What an advisor sees: everything, including hidden/below-threshold
     * results, so they retain full visibility into what the algorithm
     * actually produced.
     */
    public function forAdvisor(CareSeeker $careSeeker): Collection
    {
        return $this->getRecommendations($careSeeker);
    }

    /**
     * Must be called after any direct mutation to a MatchResult's
     * curation flags (approve, hide, shortlist, reorder) — those happen
     * via $matchResult->update() directly in the controllers, bypassing
     * this service entirely, so the read cache has no way to know the
     * underlying data changed. Without this, a family/advisor toggling
     * a flag would not see it reflected for up to CACHE_TTL_SECONDS.
     * Caught via end-to-end smoke testing before shipping — regenerating
     * results (which already calls this) masked the gap in every earlier
     * test that happened to also regenerate.
     */
    public function invalidateCache(CareSeeker $careSeeker): void
    {
        Cache::forget($this->cacheKey($careSeeker));
    }

    private function isStale(\App\Models\NeedsAssessment $assessment): bool
    {
        if (!$assessment->last_matched_at) {
            return true;
        }

        return $assessment->careSeeker->updated_at->gt($assessment->last_matched_at)
            || $assessment->updated_at->gt($assessment->last_matched_at);
    }

    private function cacheKey(CareSeeker $careSeeker): string
    {
        return "recommendations:care_seeker:{$careSeeker->id}";
    }
}
