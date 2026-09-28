<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Agency\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly PlanLimitService $planLimits,
    ) {}

    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $plans = Plan::active()->with('features.feature')->get();
        $currentPlan = $agency->subscription?->plan;
        $canFeature = $this->planLimits->canEnableFeaturedListing($agency);

        return view('agency.settings', compact('agency', 'plans', 'currentPlan', 'canFeature'));
    }

    /**
     * Toggle featured listing — gated by the agency's current plan.
     * Real Stripe checkout for plan upgrades is Phase 15; for now, plan
     * changes are display-only (see settings view note to the owner).
     */
    public function toggleFeatured(Request $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);

        if (!$this->planLimits->canEnableFeaturedListing($agency)) {
            return back()->withErrors([
                'plan' => 'Featured Listing is available on the Professional plan and above. Upgrade your plan to enable it.',
            ]);
        }

        $agency->update(['is_featured' => !$agency->is_featured]);

        activity()->causedBy($request->user())->performedOn($agency)
            ->log($agency->is_featured ? 'Featured listing enabled' : 'Featured listing disabled');

        return back()->with('status', 'featured-toggled');
    }
}
