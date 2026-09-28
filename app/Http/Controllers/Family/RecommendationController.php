<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\ShortlistMatchResultRequest;
use App\Models\CareSeeker;
use App\Models\MatchResult;
use App\Services\Matching\AgencyRecommendationService;
use App\Services\Matching\MatchExplanationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The family-facing Recommended Agencies page — authorizes entirely
 * through CareSeekerPolicy (Phase 9), no separate MatchResult policy
 * needed: every action here operates in the context of "this family's
 * own Care Seeker," which CareSeekerPolicy::view()/update() already
 * governs correctly.
 */
class RecommendationController extends Controller
{
    public function __construct(
        private readonly AgencyRecommendationService $recommendations,
        private readonly MatchExplanationService $explanations,
    ) {}

    public function index(Request $request, CareSeeker $careSeeker): View
    {
        $this->authorize('view', $careSeeker);

        if (!$careSeeker->latestCompletedAssessment()) {
            return view('family.recommendations.needs-assessment-required', compact('careSeeker'));
        }

        $results = $this->recommendations->forFamily($careSeeker, includeBelowThreshold: $request->boolean('show_all'));

        $sort = $request->input('sort', 'score');
        $results = match ($sort) {
            'distance' => $results->sortBy(fn (MatchResult $r) => $r->score_breakdown['distance_miles'] ?? PHP_FLOAT_MAX),
            'cost' => $results->sortBy(fn (MatchResult $r) => (float) ($r->agency->min_monthly_cost ?? PHP_FLOAT_MAX)),
            'rating' => $results->sortByDesc(fn (MatchResult $r) => (float) ($r->agency->review_score ?? 0)),
            default => $results->sortByDesc('compatibility_score'),
        };

        if ($request->filled('care_type')) {
            $results = $results->filter(fn (MatchResult $r) => $r->agency->category?->code === $request->care_type);
        }
        if ($request->filled('max_cost')) {
            $results = $results->filter(fn (MatchResult $r) => ($r->agency->min_monthly_cost ?? 0) <= $request->max_cost);
        }
        if ($request->filled('min_score')) {
            $results = $results->filter(fn (MatchResult $r) => (float) $r->compatibility_score >= (float) $request->min_score);
        }
        if ($request->filled('max_distance')) {
            $results = $results->filter(function (MatchResult $r) use ($request) {
                $miles = $r->score_breakdown['distance_miles'] ?? null;
                // Distance is unknown for this pair (no coordinates on
                // either side) — don't exclude it from a distance filter,
                // since there's no reliable basis to say it fails the
                // filter; only exclude pairs where we positively know the
                // distance exceeds the requested max.
                return $miles === null || $miles <= (float) $request->max_distance;
            });
        }
        if ($request->filled('min_rating')) {
            $results = $results->filter(fn (MatchResult $r) => (float) ($r->agency->review_score ?? 0) >= (float) $request->min_rating);
        }

        $explanations = $results->mapWithKeys(fn (MatchResult $r) => [$r->id => $this->explanations->explain($r->score_breakdown)]);
        $favoriteAgencyIds = $careSeeker->family->favorites()->pluck('agency_id');

        return view('family.recommendations.index', compact('careSeeker', 'results', 'explanations', 'sort', 'favoriteAgencyIds'));
    }

    public function regenerate(Request $request, CareSeeker $careSeeker): RedirectResponse
    {
        $this->authorize('view', $careSeeker);

        $this->recommendations->getRecommendations($careSeeker, forceRegenerate: true);

        activity()->causedBy($request->user())->performedOn($careSeeker)->log('Recommendations refreshed');

        return back()->with('status', 'recommendations-refreshed');
    }

    public function toggleShortlist(ShortlistMatchResultRequest $request, CareSeeker $careSeeker, MatchResult $matchResult): RedirectResponse
    {
        $this->authorizeMatchResultBelongsToCareSeeker($matchResult, $careSeeker);

        $matchResult->update(['is_family_shortlisted' => !$matchResult->is_family_shortlisted]);
        $this->recommendations->invalidateCache($careSeeker);

        activity()->causedBy($request->user())->performedOn($matchResult)->log(
            $matchResult->is_family_shortlisted ? 'Agency added to shortlist' : 'Agency removed from shortlist'
        );

        return back()->with('status', $matchResult->is_family_shortlisted ? 'shortlisted' : 'unshortlisted');
    }

    private function authorizeMatchResultBelongsToCareSeeker(MatchResult $matchResult, CareSeeker $careSeeker): void
    {
        abort_unless($matchResult->needsAssessment->care_seeker_id === $careSeeker->id, 403);
    }
}
