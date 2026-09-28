<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agency-facing performance analytics — deliberately limited to
 * aggregate, non-scoring signals: favorite count, review rating, and
 * referral/lead volume (via the existing Phase 7 referrals table). This
 * MUST NEVER expose compatibility_score, score_breakdown, ranking
 * position, or any other Matching Engine internal — "Agencies must NOT
 * see their internal matching score... see the matching algorithm...
 * manipulate recommendation scores" is an explicit, hard Phase 12
 * requirement verified by AgencyExclusionTest and extended here. An
 * aggregate count like "shortlisted by N families" is analogous to a
 * normal SaaS "profile views" metric and does not reveal how the score
 * was computed or what it was for any specific family.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);

        $stats = [
            'favorites_count' => $agency->favoritedBy()->count(),
            'review_score' => $agency->review_score,
            'total_referrals' => $agency->referrals()->count(),
            'referrals_by_status' => $agency->referrals()
                ->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status'),
            // Deliberately a count only — never the score, never which
            // families, never the ranking. See class docblock.
            'times_shortlisted_by_families' => \App\Models\MatchResult::whereHas('agency', fn ($q) => $q->where('id', $agency->id))
                ->where('is_family_shortlisted', true)->count(),
        ];

        return view('agency.analytics.index', compact('agency', 'stats'));
    }
}
