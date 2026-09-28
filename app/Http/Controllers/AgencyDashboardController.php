<?php

namespace App\Http\Controllers;

use App\Services\Agency\AgencyAnalyticsService;
use App\Services\Agency\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgencyDashboardController extends Controller
{
    public function __construct(
        private readonly AgencyAnalyticsService $analytics,
        private readonly PlanLimitService $planLimits,
    ) {}

    public function dashboard(Request $request): View|RedirectResponse
    {
        $agency = $request->user()->currentAgency();

        if (!$agency) {
            return redirect()->route('agency.register.step1');
        }

        if (!$agency->isOnboardingComplete()) {
            return redirect()->route('agency.register.step' . $agency->onboarding_step);
        }

        $this->authorize('view', $agency);

        $agency->load('statusHistory');
        $summary = $this->analytics->summary($agency);
        $planName = $this->planLimits->currentPlanName($agency);
        $recentLeads = $agency->referrals()->latest()->limit(5)->get();

        // Referral Inbox summary + Pending Actions (Phase 13) — the
        // agency's most time-sensitive items surfaced right on the
        // dashboard rather than requiring a trip to the Referral Inbox
        // to discover what needs a response.
        $pendingReferrals = $agency->referrals()
            ->where('status', \App\Enums\ReferralStatus::SentToAgency->value)
            ->with(['careSeeker', 'family.user'])
            ->latest()->limit(5)->get();
        $referralCounts = $agency->referrals()
            ->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');

        // Upcoming Tours
        $upcomingTours = \App\Models\TourRequest::where('agency_id', $agency->id)
            ->with('careSeeker')->upcoming()->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('requested_date')->limit(5)->get();

        // Reviews (Phase 14)
        $totalReviews = $agency->reviews()->published()->count();
        $recentReviews = $agency->reviews()->published()->with('reply')->latest()->limit(3)->get();
        $pendingResponsesCount = $agency->reviews()->published()->whereDoesntHave('reply')->count();
        $reviewRatingBreakdown = $agency->reviews()->published()
            ->selectRaw('round(overall_rating) as rounded_rating, count(*) as count')
            ->groupBy('rounded_rating')->orderByDesc('rounded_rating')->pluck('count', 'rounded_rating');

        // Billing (Phase 16) — Current Plan, Billing Status, Next
        // Renewal, recent Payment History, Featured Listing Status.
        $subscription = $agency->subscription()->with('plan')->first();
        $recentPayments = \App\Models\Payment::whereHas('invoice', fn ($q) => $q->where('agency_id', $agency->id))
            ->with('invoice')->latest()->limit(5)->get();

        return view('agency.dashboard', compact(
            'agency', 'summary', 'planName', 'recentLeads', 'pendingReferrals', 'referralCounts', 'upcomingTours',
            'totalReviews', 'recentReviews', 'pendingResponsesCount', 'reviewRatingBreakdown',
            'subscription', 'recentPayments'
        ));
    }
}
