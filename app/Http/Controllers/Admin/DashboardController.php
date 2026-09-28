<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Family;
use App\Models\User;
use App\Services\Billing\FinancialAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-admin-panel');

        $stats = [
            'total_users'    => User::count(),
            'total_agencies' => Agency::count(),
            'total_families' => Family::count(),
            'pending_agencies' => Agency::inModerationQueue()->count(),
        ];

        // Billing KPIs (Phase 16) — Revenue, Subscription, and Payment
        // KPIs for the Super Admin dashboard at-a-glance, with the full
        // breakdown available on the Reports page.
        $financialAnalytics = app(FinancialAnalyticsService::class);
        $billingStats = [
            'total_revenue' => $financialAnalytics->totalRevenue(),
            'mrr' => $financialAnalytics->monthlyRecurringRevenue(),
            'arr' => $financialAnalytics->annualRecurringRevenue(),
            'active_subscriptions' => $financialAnalytics->activeSubscriptionsCount(),
            'trial_users' => $financialAnalytics->trialUsersCount(),
            'failed_payments_count' => $financialAnalytics->failedPaymentsCount(),
            'pending_refunds_count' => $financialAnalytics->pendingRefundsCount(),
        ];
        $recentTransactions = $financialAnalytics->recentTransactions(5);

        return view('dashboards.admin', compact('stats', 'billingStats', 'recentTransactions'));
    }
}
