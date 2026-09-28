<?php

namespace App\Http\Controllers\Advisor;

use App\Http\Controllers\Controller;
use App\Models\Advisor;
use App\Services\Advisor\AdvisorDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdvisorDashboardService $dashboard,
    ) {}

    public function index(Request $request): View
    {
        // Self-heals a missing Advisor row the same way Family's
        // dashboard does (see EnsureFamilyRecordExists, Phase 9) — an
        // advisor-role account should always be able to reach this page.
        $advisor = Advisor::firstOrCreate(['user_id' => $request->user()->id], ['is_active' => true]);

        $this->authorize('view', $advisor);

        $summary = $this->dashboard->summary($advisor);
        $recentLeads = $advisor->leads()->with('family.user')->latest()->limit(5)->get();
        $upcomingTasks = $advisor->tasks()->pending()->orderBy('due_at')->limit(5)->get();
        $recentActivity = app(\App\Services\Advisor\LeadTimelineService::class)->forAdvisor($advisor, limit: 8);

        // Surfaces the "Generate Recommendations" action directly on the
        // dashboard rather than requiring an advisor to already know to
        // open a specific Lead and find it there — a real discoverability
        // gap reported and fixed during the Phase 12 completion pass.
        // A lead "needs" recommendations once its Care Seeker has a
        // completed Needs Assessment but no match_results exist yet for
        // that assessment.
        $leadsNeedingRecommendations = $advisor->leads()
            ->whereNotNull('care_seeker_id')
            ->with(['careSeeker.needsAssessments', 'family.user'])
            ->whereHas('careSeeker.needsAssessments', fn ($q) => $q->where('status', 'completed'))
            ->get()
            ->filter(function ($lead) {
                $assessment = $lead->careSeeker->latestCompletedAssessment();
                return $assessment && $assessment->matchResults()->count() === 0;
            })
            ->take(5);

        $teamSummary = null;
        if ($advisor->teamMembers()->exists()) {
            $teamSummary = $this->dashboard->teamSummary($advisor);
        }

        // Referral KPIs + Conversion Rate + Pending Follow-ups (Phase 13)
        $advisorIds = $advisor->teamMembers()->exists()
            ? $advisor->teamMembers()->pluck('id')->push($advisor->id)
            : collect([$advisor->id]);

        $totalReferrals = \App\Models\Referral::whereIn('advisor_id', $advisorIds)->count();
        $convertedReferrals = \App\Models\Referral::whereIn('advisor_id', $advisorIds)
            ->where('status', \App\Enums\ReferralStatus::Converted->value)->count();
        $referralConversionRate = $totalReferrals > 0 ? round(($convertedReferrals / $totalReferrals) * 100, 1) : 0.0;

        $referralKpis = [
            'total' => $totalReferrals,
            'pending' => \App\Models\Referral::whereIn('advisor_id', $advisorIds)
                ->whereIn('status', [\App\Enums\ReferralStatus::Pending->value, \App\Enums\ReferralStatus::SentToAgency->value])->count(),
            'accepted' => \App\Models\Referral::whereIn('advisor_id', $advisorIds)
                ->where('status', \App\Enums\ReferralStatus::AgencyAccepted->value)->count(),
            'converted' => $convertedReferrals,
            'conversion_rate' => $referralConversionRate,
        ];

        $upcomingTours = \App\Models\TourRequest::whereIn('lead_id', $advisor->leads()->pluck('id'))
            ->with(['agency', 'lead.family.user'])->upcoming()->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('requested_date')->limit(5)->get();

        $pendingFollowUps = \App\Models\Referral::whereIn('advisor_id', $advisorIds)
            ->where('status', \App\Enums\ReferralStatus::FollowUpRequired->value)
            ->with(['agency', 'careSeeker'])->latest()->limit(5)->get();

        // Families Awaiting Reviews / Completed Move-Ins Awaiting
        // Feedback (Phase 14)
        $awaitingReviewReferrals = \App\Models\Referral::whereIn('advisor_id', $advisorIds)
            ->whereIn('status', array_map(fn ($s) => $s->value, app(\App\Services\Review\ReviewSubmissionService::class)->eligibleStatuses()))
            ->whereDoesntHave('review')
            ->with(['agency', 'careSeeker', 'family.user'])
            ->latest()->limit(5)->get();

        return view('advisor.dashboard', compact(
            'advisor', 'summary', 'recentLeads', 'upcomingTasks', 'recentActivity', 'teamSummary',
            'leadsNeedingRecommendations', 'referralKpis', 'upcomingTours', 'pendingFollowUps', 'awaitingReviewReferrals'
        ));
    }
}
