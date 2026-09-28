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
            'referralStatusCounts', 'referralSummary', 'conversionFunnel', 'recentReferralActivity'
        ));
    }
}
