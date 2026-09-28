<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shows the agency's real, existing subscription row (auto-provisioned
 * on the Free plan at registration — Phase 7's AgencyProvisioningService,
 * see DATABASE_DECISIONS.md §12 for why that placeholder exists ahead of
 * real Stripe/Cashier billing, Phase 16) and every plan's real feature
 * set for comparison. No fake checkout/upgrade flow is presented, since
 * there's no real billing integration to back it — upgrading is
 * explicitly labeled as coming later rather than silently doing nothing
 * or faking success.
 */
class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);

        $subscription = $agency->subscription()->with('plan.features')->first();
        $allPlans = Plan::where('is_active', true)->with('features')->orderBy('sort_order')->get();

        return view('agency.subscription.index', compact('agency', 'subscription', 'allPlans'));
    }
}
