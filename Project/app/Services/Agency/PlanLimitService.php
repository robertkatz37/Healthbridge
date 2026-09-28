<?php

namespace App\Services\Agency;

use App\Models\Agency;

/**
 * Resolves subscription plan feature values/limits for an Agency and checks
 * usage against them. Reads from the plan_features pivot seeded in Phase 2
 * (PlanSeeder) — codes: max_locations, max_leads_per_month, featured_listing,
 * max_staff_accounts, analytics_dashboard, priority_support.
 *
 * Every Agency is guaranteed to have an active Subscription because
 * AgencyProvisioningService auto-provisions the Free plan at creation time
 * (see DATABASE_DECISIONS.md §12) — real Stripe billing arrives in Phase 15.
 */
class PlanLimitService
{
    /**
     * Raw feature value as stored (string) — "true"/"false", an integer
     * string, or "unlimited". Returns null if the agency has no active
     * subscription or the feature isn't defined on their plan.
     */
    public function featureValue(Agency $agency, string $featureCode): ?string
    {
        $subscription = $agency->subscription;

        if (!$subscription || !$subscription->plan) {
            return null;
        }

        return $subscription->plan->features()
            ->whereHas('feature', fn ($q) => $q->where('code', $featureCode))
            ->first()?->value;
    }

    /**
     * Boolean-type feature check (e.g. featured_listing, analytics_dashboard).
     */
    public function hasFeature(Agency $agency, string $featureCode): bool
    {
        return $this->featureValue($agency, $featureCode) === 'true';
    }

    /**
     * Integer-type limit, or PHP_INT_MAX for "unlimited". Returns 0 if
     * undefined (fails closed — no plan means no allowance).
     */
    public function limit(Agency $agency, string $featureCode): int
    {
        $value = $this->featureValue($agency, $featureCode);

        if ($value === null) {
            return 0;
        }

        if ($value === 'unlimited') {
            return PHP_INT_MAX;
        }

        return (int) $value;
    }

    public function canAddStaff(Agency $agency): bool
    {
        $limit = $this->limit($agency, 'max_staff_accounts');
        $current = $agency->staff()->count();

        return $current < $limit;
    }

    public function remainingStaffSlots(Agency $agency): int|string
    {
        $limit = $this->limit($agency, 'max_staff_accounts');

        if ($limit === PHP_INT_MAX) {
            return 'Unlimited';
        }

        return max(0, $limit - $agency->staff()->count());
    }

    public function canEnableFeaturedListing(Agency $agency): bool
    {
        return $this->hasFeature($agency, 'featured_listing');
    }

    public function hasAnalyticsDashboard(Agency $agency): bool
    {
        return $this->hasFeature($agency, 'analytics_dashboard');
    }

    public function currentPlanName(Agency $agency): string
    {
        return $agency->subscription?->plan?->name ?? 'No Plan';
    }
}
