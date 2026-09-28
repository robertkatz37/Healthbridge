<?php

namespace App\Services\Matching;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\CareSeeker;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resolves the configurable weight/threshold settings (Phase 10's
 * SettingsService, group 'matching') and applies HARD filters — rules
 * that exclude an agency from consideration entirely rather than merely
 * lowering its score. Distinguishing hard filters from soft-scored
 * factors matters: a hard filter (published status, distance beyond the
 * configured max) means the recommendation would be actively wrong to
 * show at all, whereas everything else (care type, budget, etc.) is a
 * fit *degree*, still worth surfacing at a lower score so a family can
 * see the full landscape of options.
 */
class MatchingRuleEngine
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function weights(): array
    {
        return $this->settings->get('matching_weights', []);
    }

    public function featuredBoost(): float
    {
        return (float) $this->settings->get('matching_featured_boost', 3);
    }

    public function maxDistanceMiles(): int
    {
        return (int) $this->settings->get('matching_max_distance_miles', 100);
    }

    public function minScoreThreshold(): int
    {
        return (int) $this->settings->get('matching_min_score_threshold', 30);
    }

    /**
     * Candidate agencies before scoring — only the non-negotiable hard
     * filters are applied here (published, has a category). Distance
     * is filtered separately in MatchingEngineService after coordinates
     * are known, since it needs a computed value per-agency rather than
     * a plain WHERE clause.
     */
    public function candidateQuery(CareSeeker $careSeeker): Builder
    {
        return Agency::query()
            ->published()
            ->with(['category', 'services', 'coverage', 'certifications', 'pricing']);
    }

    /**
     * True if this agency should be excluded entirely (not just scored
     * low) given the computed distance. Null distance (coordinates
     * unavailable for one or both sides) never hard-excludes — there's
     * no reliable basis to reject on unknown information.
     */
    public function passesDistanceFilter(?float $distanceMiles): bool
    {
        if ($distanceMiles === null) {
            return true;
        }

        return $distanceMiles <= $this->maxDistanceMiles();
    }
}
