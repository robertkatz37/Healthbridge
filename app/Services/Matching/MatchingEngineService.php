<?php

namespace App\Services\Matching;

use App\Models\Agency;
use App\Models\CareSeeker;
use App\Models\NeedsAssessment;
use Illuminate\Support\Collection;

/**
 * The top-level computation pipeline: given a Care Seeker (via their
 * latest completed NeedsAssessment — matching is explicitly "based on
 * the completed Needs Assessment" per the Phase 12 objectives, honoring
 * the existing match_results.needs_assessment_id schema from Phase 2
 * rather than changing it to key off care_seeker_id directly), find
 * candidate agencies, score each one, and persist the results.
 *
 * Kept separate from AgencyRecommendationService: this class is pure
 * computation (no caching/staleness decisions, no presentation
 * concerns) — AgencyRecommendationService is the facade controllers
 * actually call, deciding *whether* to invoke this at all.
 */
class MatchingEngineService
{
    public function __construct(
        private readonly MatchingRuleEngine $rules,
        private readonly MatchingScoreService $scorer,
        private readonly DistanceCalculatorService $distance,
    ) {}

    /**
     * Computes and persists match_results for every viable candidate
     * agency against this Care Seeker's latest completed assessment.
     * Existing rows for that assessment are replaced (updateOrCreate per
     * agency) rather than duplicated, and any prior row for an agency
     * that no longer qualifies as a candidate is removed — so
     * regenerating always leaves match_results as an accurate snapshot
     * of the current candidate set, never a stale accumulation.
     *
     * @return Collection<int, \App\Models\MatchResult>
     */
    public function generateRecommendations(CareSeeker $careSeeker): Collection
    {
        $assessment = $careSeeker->latestCompletedAssessment();

        if (!$assessment) {
            throw new \RuntimeException('Care Seeker has no completed Needs Assessment to match against.');
        }

        $candidates = $this->rules->candidateQuery($careSeeker)->get();

        $keptAgencyIds = [];
        $results = collect();

        foreach ($candidates as $agency) {
            $scoreResult = $this->scorer->score($careSeeker, $agency);

            if (!$this->rules->passesDistanceFilter($scoreResult['distance_miles'])) {
                continue;
            }

            $keptAgencyIds[] = $agency->id;

            $results->push($this->persistResult($assessment, $agency, $scoreResult));
        }

        // Removes stale rows for agencies that no longer qualify (e.g.
        // an agency was unpublished, or now exceeds the configured max
        // distance after a weight/threshold change) — otherwise a family
        // could keep seeing a recommendation that's no longer valid.
        $assessment->matchResults()
            ->whereNotIn('agency_id', $keptAgencyIds)
            ->delete();

        // Stamped unconditionally — see the migration's docblock for why
        // this can't be inferred from match_results.updated_at alone
        // (Eloquent skips the UPDATE, and therefore the timestamp bump,
        // when a regenerated score happens to be byte-identical to what
        // was already stored).
        $assessment->update(['last_matched_at' => now()]);

        return $results->sortByDesc('compatibility_score')->values();
    }

    private function persistResult(NeedsAssessment $assessment, Agency $agency, array $scoreResult): \App\Models\MatchResult
    {
        return $assessment->matchResults()->updateOrCreate(
            ['agency_id' => $agency->id],
            [
                'compatibility_score' => $scoreResult['total_score'],
                'score_breakdown' => $scoreResult,
            ]
        );
    }
}
