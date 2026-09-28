<?php

namespace App\Services\Agency;

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Creates the Agency record and its default subscription when an
 * Agency Owner begins onboarding (wizard Step 1).
 *
 * Every Agency must carry a Subscription row because Cashier's schema
 * (subscriptions.stripe_id, stripe_status) is NOT NULL — pre-built in
 * Phase 2 ahead of the real Stripe integration (Phase 15). Until then we
 * provision the Free plan with a locally-generated placeholder stripe_id
 * ("local_<uuid>") and stripe_status "active" so every agency is
 * plan-gated correctly from day one. Phase 15 will replace this
 * placeholder subscription with a real Stripe Checkout-created one on
 * upgrade — see DATABASE_DECISIONS.md §12.
 */
class AgencyProvisioningService
{
    public function createDraftAgency(User $owner, array $basicInfo): Agency
    {
        $agency = Agency::create([
            'user_id' => $owner->id,
            'agency_category_id' => $basicInfo['agency_category_id'],
            'name' => $basicInfo['name'],
            'slug' => $this->uniqueSlug($basicInfo['name']),
            'description' => $basicInfo['description'] ?? null,
            'phone' => $basicInfo['phone'] ?? null,
            'email' => $basicInfo['email'] ?? $owner->email,
            'address' => $basicInfo['address'] ?? null,
            'city' => $basicInfo['city'] ?? null,
            'state' => $basicInfo['state'] ?? null,
            'status' => AgencyStatus::Draft->value,
            'onboarding_step' => 2,
        ]);

        $this->provisionFreeSubscription($agency);

        return $agency;
    }

    public function provisionFreeSubscription(Agency $agency): Subscription
    {
        // Query directly rather than via $agency->subscription — accessing
        // the relation property here would lazy-load and cache a null
        // result on this object instance before the row exists below,
        // leaving $agency->subscription permanently null for the rest of
        // this request even after the row is created.
        $existing = Subscription::where('agency_id', $agency->id)->first();
        if ($existing) {
            return $existing;
        }

        $freePlan = Plan::where('code', 'free')->firstOrFail();

        $subscription = Subscription::create([
            'agency_id' => $agency->id,
            'plan_id' => $freePlan->id,
            'type' => 'default',
            'stripe_id' => 'local_' . Str::uuid(),
            'stripe_status' => 'active',
        ]);

        // Ensure the caller's $agency instance reflects the new subscription
        // immediately, in case ->subscription was already accessed/cached.
        $agency->setRelation('subscription', $subscription);

        return $subscription;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Agency::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }
}
