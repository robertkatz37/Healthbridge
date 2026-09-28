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

        return view('agency.dashboard', compact(
            'agency', 'summary', 'planName', 'recentLeads', 'pendingReferrals', 'referralCounts', 'upcomingTours'
        ));
    }
}
