<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Enums\LeadStatus;
use App\Enums\ReferralStatus;
use App\Http\Controllers\Controller;
use App\Models\Advisor;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\CareSeeker;
use App\Models\Family;
use App\Models\Lead;
use App\Models\MatchResult;
use App\Models\Referral;
use App\Models\ReferralStatusHistory;
use App\Models\TourRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Platform-wide Reports & Analytics — every figure here is a real,
 * computed aggregate over live data (no placeholder/mock numbers).
 * Deliberately scoped to what's genuinely buildable from data the
 * platform already has as of Phase 12 (agencies, families, leads,
 * matching) rather than speculative metrics (e.g. revenue, which has
 * no real billing integration yet — Phase 16). Gated the same way as
 * the Activity Log: platform-administration concern, not a per-
 * resource Policy.
 */
class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('manage-platform-settings'), 403);

        $financialAnalytics = app(\App\Services\Billing\FinancialAnalyticsService::class);
        $financialSummary = [
            'total_revenue' => $financialAnalytics->totalRevenue(),
            'revenue_this_month' => $financialAnalytics->revenueThisMonth(),
            'mrr' => $financialAnalytics->monthlyRecurringRevenue(),
            'arr' => $financialAnalytics->annualRecurringRevenue(),
            'active_subscriptions' => $financialAnalytics->activeSubscriptionsCount(),
            'trial_users' => $financialAnalytics->trialUsersCount(),
            'churn_rate' => $financialAnalytics->churnRate30Days(),
            'failed_payments_count' => $financialAnalytics->failedPaymentsCount(),
            'total_refunded' => $financialAnalytics->totalRefunded(),
            'pending_refunds_count' => $financialAnalytics->pendingRefundsCount(),
            'featured_listing_revenue' => $financialAnalytics->featuredListingRevenue(),
        ];
        $activeSubscriptionsByPlan = $financialAnalytics->activeSubscriptionsByPlan();
        $topSellingPlans = $financialAnalytics->topSellingPlans();
        $recentFailedPayments = $financialAnalytics->recentFailedPayments();
        $recentTransactions = $financialAnalytics->recentTransactions();
        $pendingRefunds = $financialAnalytics->pendingRefunds();

        $agencyStatusCounts = Agency::selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        $agencyCategoryCounts = AgencyCategory::withCount('agencies')->orderByDesc('agencies_count')->get();

        $leadStatusCounts = Lead::selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        $totalLeads = Lead::count();
        $convertedLeads = Lead::where('status', LeadStatus::Converted->value)->count();
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0.0;

        $matchResultStats = [
            'total_generated' => MatchResult::count(),
            'average_score' => round((float) MatchResult::avg('compatibility_score'), 1),
            'total_shortlisted' => MatchResult::where('is_family_shortlisted', true)->count(),
            'total_advisor_approved' => MatchResult::where('is_advisor_approved', true)->count(),
        ];

        $leadsPerDay = Lead::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')->orderBy('date')->get();

        // Referral Analytics (Phase 13)
        $referralStatusCounts = Referral::selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        $totalReferrals = Referral::count();
        $convertedReferrals = Referral::where('status', ReferralStatus::Converted->value)->count();
        $referralConversionRate = $totalReferrals > 0 ? round(($convertedReferrals / $totalReferrals) * 100, 1) : 0.0;

        // Conversion Funnel — how many referrals have reached at least
        // each stage, in pipeline order, giving a classic drop-off funnel
        // shape rather than just a flat per-status count.
        $funnelStages = [
            ReferralStatus::SentToAgency, ReferralStatus::AgencyAccepted, ReferralStatus::TourScheduled,
            ReferralStatus::TourCompleted, ReferralStatus::MoveInConfirmed, ReferralStatus::Converted,
        ];
        $reachedStatusValues = fn (ReferralStatus $stage) => Referral::whereIn('status', [
            $stage->value,
            ...array_map(fn ($s) => $s->value, array_slice($funnelStages, array_search($stage, $funnelStages) + 1)),
        ])->count();
        $conversionFunnel = collect($funnelStages)->mapWithKeys(fn (ReferralStatus $stage) => [
            $stage->label() => $reachedStatusValues($stage),
        ]);

        $pendingTours = TourRequest::whereIn('status', ['requested', 'confirmed'])
            ->upcoming()->count();

        $recentReferralActivity = ReferralStatusHistory::with(['referral.agency', 'referral.careSeeker', 'changedBy'])
            ->latest()->limit(10)->get();

        // Review Analytics (Phase 14)
        $reviewSummary = [
            'total_published' => \App\Models\Review::published()->count(),
            'pending_moderation' => \App\Models\Review::where('status', 'pending_moderation')->count(),
            'reported' => \App\Models\ReviewReport::where('status', 'pending')->count(),
            'average_platform_rating' => round((float) (\App\Models\Review::published()->avg('overall_rating') ?? 0), 2),
        ];

        // CMS Analytics (Phase 15)
        $cmsSummary = [
            'published_pages' => \App\Models\CmsPage::where('status', 'published')->count(),
            'draft_pages' => \App\Models\CmsPage::where('status', 'draft')->count(),
            'published_posts' => \App\Models\BlogPost::where('status', 'published')->count(),
            'scheduled_posts' => \App\Models\BlogPost::where('status', 'scheduled')->count(),
        ];

        // Filtered in PHP rather than via GROUP BY + HAVING on a
        // withCount()-derived column — that combination behaves
        // differently across database engines (real MySQL under
        // ONLY_FULL_GROUP_BY rejected grouping by the primary key alone
        // here, even though SQLite accepted it). See
        // ReviewAwardService::eligibleAgencies() for the same fix and
        // full explanation.
        $ratedAgencies = Agency::whereHas('reviews', fn ($q) => $q->where('status', 'published'))
            ->whereNotNull('review_score')
            ->withCount(['reviews as review_count' => fn ($q) => $q->where('status', 'published')])
            ->get()
            ->filter(fn ($agency) => $agency->review_count >= 3);

        $topRatedAgencies = $ratedAgencies->sortByDesc('review_score')->take(5)->values();
        $lowestRatedAgencies = $ratedAgencies->sortBy('review_score')->take(5)->values();

        $referralSummary = [
            'total_referrals' => $totalReferrals,
            'conversion_rate' => $referralConversionRate,
            'pending_tours' => $pendingTours,
            'active_referrals' => Referral::open()->count(),
        ];

        $summary = [
            'total_agencies' => Agency::count(),
            'published_agencies' => Agency::where('status', AgencyStatus::Published->value)->count(),
            'total_families' => Family::count(),
            'total_care_seekers' => CareSeeker::count(),
            'total_advisors' => Advisor::count(),
            'active_advisors' => Advisor::where('is_active', true)->count(),
            'total_leads' => $totalLeads,
            'conversion_rate' => $conversionRate,
        ];

        return view('admin.reports.index', compact(
            'summary', 'agencyStatusCounts', 'agencyCategoryCounts',
            'leadStatusCounts', 'matchResultStats', 'leadsPerDay',
            'referralStatusCounts', 'referralSummary', 'conversionFunnel', 'recentReferralActivity',
            'reviewSummary', 'topRatedAgencies', 'lowestRatedAgencies', 'cmsSummary',
            'financialSummary', 'activeSubscriptionsByPlan', 'topSellingPlans',
            'recentFailedPayments', 'recentTransactions', 'pendingRefunds'
        ));
    }
}
