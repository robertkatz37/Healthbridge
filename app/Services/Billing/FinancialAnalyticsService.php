<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Refund;
use App\Models\Subscription;

/**
 * Every figure the Super Admin Financial Reports need, computed live
 * from real invoice/payment/subscription/refund data — no cached
 * snapshot table, matching the existing ReportsController's approach
 * for every other metric on that page.
 */
class FinancialAnalyticsService
{
    public function totalRevenue(): float
    {
        return (float) Payment::succeeded()->sum('amount') - (float) Payment::succeeded()->sum('refunded_amount');
    }

    public function revenueThisMonth(): float
    {
        return (float) Payment::succeeded()->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount');
    }

    /**
     * Monthly Recurring Revenue — the sum of every active paid
     * subscription's monthly-equivalent price (yearly plans divided by
     * 12), which is the standard MRR definition. The Free plan
     * contributes 0 by construction (price_monthly is 0).
     */
    public function monthlyRecurringRevenue(): float
    {
        $active = Subscription::active()->whereNull('ends_at')->with('plan')->get();

        return round($active->sum(function (Subscription $subscription) {
            if (!$subscription->plan) {
                return 0;
            }
            $isYearly = str_contains((string) $subscription->stripe_price, 'yearly');

            return $isYearly ? (float) $subscription->plan->price_yearly / 12 : (float) $subscription->plan->price_monthly;
        }), 2);
    }

    /**
     * Annual Recurring Revenue — the standard MRR x 12 definition,
     * not a separately-tracked figure (a subscription's billing
     * cycle doesn't change what ARR means, only what MRR is derived
     * from).
     */
    public function annualRecurringRevenue(): float
    {
        return round($this->monthlyRecurringRevenue() * 12, 2);
    }

    public function trialUsersCount(): int
    {
        return Subscription::whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())->count();
    }

    public function activeSubscriptionsCount(): int
    {
        return Subscription::active()->count();
    }

    public function activeSubscriptionsByPlan(): \Illuminate\Support\Collection
    {
        return Subscription::active()->with('plan')->get()
            ->groupBy(fn (Subscription $s) => $s->plan?->name ?? 'Unknown')
            ->map->count()
            ->sortDesc();
    }

    public function topSellingPlans(int $limit = 5): \Illuminate\Support\Collection
    {
        return Plan::withCount(['subscriptions' => fn ($q) => $q->where('stripe_status', 'active')])
            ->orderByDesc('subscriptions_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Churn rate over the trailing 30 days: subscriptions that ended
     * in that window, divided by subscriptions that were active at
     * the start of it. Returns 0 if there was nothing to churn from,
     * rather than dividing by zero.
     */
    public function churnRate30Days(): float
    {
        $windowStart = now()->subDays(30);

        $activeAtStart = Subscription::where('created_at', '<=', $windowStart)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $windowStart))
            ->count();

        if ($activeAtStart === 0) {
            return 0.0;
        }

        $churned = Subscription::whereNotNull('ends_at')->whereBetween('ends_at', [$windowStart, now()])->count();

        return round(($churned / $activeAtStart) * 100, 2);
    }

    public function failedPaymentsCount(): int
    {
        return Payment::failed()->count();
    }

    public function recentFailedPayments(int $limit = 10)
    {
        return Payment::failed()->with('invoice.agency')->latest()->limit($limit)->get();
    }

    public function totalRefunded(): float
    {
        return (float) Refund::where('status', RefundStatus::Processed->value)->sum('amount');
    }

    public function pendingRefundsCount(): int
    {
        return Refund::pending()->count();
    }

    public function pendingRefunds(int $limit = 10)
    {
        return Refund::pending()->with(['payment.invoice.agency', 'requester'])->latest()->limit($limit)->get();
    }

    public function featuredListingRevenue(): float
    {
        return (float) Invoice::where('invoice_type', InvoiceType::FeaturedListing->value)
            ->where('status', InvoiceStatus::Paid->value)
            ->sum('total_amount');
    }

    public function recentTransactions(int $limit = 10)
    {
        return Payment::with('invoice.agency')->latest()->limit($limit)->get();
    }
}
