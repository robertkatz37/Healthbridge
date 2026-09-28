<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Services\Family\FamilyDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly FamilyDashboardService $dashboard,
    ) {}

    public function index(Request $request): View
    {
        // EnsureFamilyRecordExists middleware (registered on the whole
        // family.* route group) guarantees this row exists before we get
        // here — see that middleware's docblock for the redirect-loop bug
        // this fixes at the root rather than patching every controller.
        $family = Family::where('user_id', $request->user()->id)->firstOrFail();

        $this->authorize('view', $family);

        $family->load(['careSeekers' => fn ($q) => $q->latest()]);
        $summary = $this->dashboard->summary($family);
        $recentFavorites = $family->favorites()->with('agency')->latest()->limit(3)->get();

        // Referral status cards (Phase 13) — one card per referral,
        // status at a glance, without needing to click into each one.
        $referrals = $family->referrals()->with('agency')->latest()->limit(5)->get();
        $referralStatusCounts = $family->referrals()
            ->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        // Upcoming Tours
        $upcomingTours = \App\Models\TourRequest::where('family_id', $family->id)
            ->with('agency')->upcoming()->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('requested_date')->limit(3)->get();

        // Referral Timeline — most recent status changes across all of
        // this family's referrals, newest first.
        $referralTimeline = \App\Models\ReferralStatusHistory::whereIn('referral_id', $family->referrals()->pluck('id'))
            ->with('referral.agency')->latest()->limit(6)->get();

        // Advisor Messages — reuses the existing Phase 9 conversation
        // system, not a new inbox.
        $advisorMessages = $family->leads()->with('advisor.user')->whereNotNull('advisor_id')->latest()->limit(1)->get();

        // My Reviews + Pending Reviews (Phase 14)
        $myReviews = $family->reviews()->with('agency')->latest()->limit(3)->get();
        $pendingReviewReferrals = \App\Models\Referral::where('family_id', $family->id)
            ->whereIn('status', array_map(fn ($s) => $s->value, app(\App\Services\Review\ReviewSubmissionService::class)->eligibleStatuses()))
            ->whereDoesntHave('review')
            ->with('agency')->get();

        return view('family.dashboard', compact(
            'family', 'summary', 'recentFavorites', 'referrals', 'referralStatusCounts',
            'upcomingTours', 'referralTimeline', 'advisorMessages', 'myReviews', 'pendingReviewReferrals'
        ));
    }
}
