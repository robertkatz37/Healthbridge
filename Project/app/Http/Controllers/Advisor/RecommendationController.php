<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Advisor\ApproveMatchResultRequest;
use App\Http\Requests\Advisor\GenerateRecommendationsRequest;
use App\Http\Requests\Advisor\HideMatchResultRequest;
use App\Http\Requests\Advisor\ReorderMatchResultsRequest;
use App\Models\Lead;
use App\Models\MatchResult;
use App\Services\Matching\AgencyRecommendationService;
use App\Services\Matching\MatchExplanationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The advisor-facing side of the Matching Engine — authorizes entirely
 * through LeadPolicy (Phase 11): every action here operates in the
 * context of "a Lead this advisor is assigned to (or manages)," which
 * LeadPolicy::view()/update() already governs correctly, including the
 * Advisor Manager team-oversight case.
 */
class RecommendationController extends Controller
{
    public function __construct(
        private readonly AgencyRecommendationService $recommendations,
        private readonly MatchExplanationService $explanations,
    ) {}

    public function index(Request $request, Lead $lead): View
    {
        $this->authorize('view', $lead);

        $careSeeker = $lead->careSeeker;
        if (!$careSeeker) {
            abort(404, 'This lead has no associated Care Seeker profile yet.');
        }

        if (!$careSeeker->latestCompletedAssessment()) {
            return view('advisor.recommendations.needs-assessment-required', compact('lead', 'careSeeker'));
        }

        $results = $this->recommendations->forAdvisor($careSeeker)->sortByDesc('compatibility_score');
        $explanations = $results->mapWithKeys(fn (MatchResult $r) => [$r->id => $this->explanations->explain($r->score_breakdown)]);
        $shortlist = $results->where('is_advisor_approved', true)->sortBy('sort_order')->values();

        // Which shortlisted agencies already have a referral in flight —
        // used to disable "Send Referral" for those specific agencies
        // and show their current status inline instead, rather than
        // letting the advisor attempt a duplicate send that the service
        // layer would just reject anyway.
        $openReferralAgencyIds = \App\Models\Referral::where('lead_id', $lead->id)->open()->pluck('agency_id');
        $referralsByAgency = \App\Models\Referral::where('lead_id', $lead->id)->latest()->get()->groupBy('agency_id');

        return view('advisor.recommendations.index', compact(
            'lead', 'careSeeker', 'results', 'explanations', 'shortlist', 'openReferralAgencyIds', 'referralsByAgency'
        ));
    }

    public function generate(GenerateRecommendationsRequest $request, Lead $lead): RedirectResponse
    {
        $careSeeker = $lead->careSeeker;
        abort_unless($careSeeker, 404);

        $this->recommendations->getRecommendations($careSeeker, forceRegenerate: true);

        activity()->causedBy($request->user())->performedOn($lead)->log('Recommendations generated');

        return redirect()->route('advisor.leads.recommendations.index', $lead)->with('status', 'recommendations-generated');
    }

    public function approve(ApproveMatchResultRequest $request, Lead $lead, MatchResult $matchResult): RedirectResponse
    {
        $this->authorizeMatchResultBelongsToLead($matchResult, $lead);

        $matchResult->update(['is_advisor_approved' => !$matchResult->is_advisor_approved]);
        $this->recommendations->invalidateCache($lead->careSeeker);

        activity()->causedBy($request->user())->performedOn($matchResult)->log(
            $matchResult->is_advisor_approved ? 'Agency recommendation approved' : 'Agency recommendation approval removed'
        );

        return back()->with('status', 'match-updated');
    }

    /**
     * "Override Recommendations manually (without changing algorithm
     * results)" — toggles visibility to the family and records why,
     * without touching compatibility_score/score_breakdown at all.
     */
    public function hide(HideMatchResultRequest $request, Lead $lead, MatchResult $matchResult): RedirectResponse
    {
        $this->authorizeMatchResultBelongsToLead($matchResult, $lead);

        $matchResult->update([
            'is_hidden_by_advisor' => !$matchResult->is_hidden_by_advisor,
            'advisor_override_note' => $request->advisor_override_note,
        ]);
        $this->recommendations->invalidateCache($lead->careSeeker);

        activity()->causedBy($request->user())->performedOn($matchResult)->log(
            $matchResult->is_hidden_by_advisor ? 'Agency recommendation hidden from family' : 'Agency recommendation restored'
        );

        return back()->with('status', 'match-updated');
    }

    /**
     * Advisor-driven manual reorder ("Build Family Shortlists") — updates
     * sort_order only, the underlying scores are untouched.
     */
    public function reorder(ReorderMatchResultsRequest $request, Lead $lead): JsonResponse
    {
        foreach ($request->order as $index => $matchResultId) {
            $matchResult = MatchResult::find($matchResultId);
            if ($matchResult && $this->matchResultBelongsToLead($matchResult, $lead)) {
                $matchResult->update(['sort_order' => $index]);
            }
        }
        $this->recommendations->invalidateCache($lead->careSeeker);

        return response()->json(['status' => 'reordered']);
    }

    private function authorizeMatchResultBelongsToLead(MatchResult $matchResult, Lead $lead): void
    {
        abort_unless($this->matchResultBelongsToLead($matchResult, $lead), 403);
    }

    private function matchResultBelongsToLead(MatchResult $matchResult, Lead $lead): bool
    {
        return $matchResult->needsAssessment->care_seeker_id === $lead->care_seeker_id;
    }
}
