<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StorePricingRequest;
use App\Models\AgencyPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PricingController extends Controller
{
    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('view', $agency);

        $pricing = $agency->pricing;

        return view('agency.pricing', compact('agency', 'pricing'));
    }

    public function store(StorePricingRequest $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();

        $agency->pricing()->create($request->validated());
        $this->syncAgencyCostRange($agency);

        activity()->causedBy($request->user())->performedOn($agency)->log('Pricing tier added');

        return back()->with('status', 'pricing-added');
    }

    public function update(StorePricingRequest $request, AgencyPricing $pricing): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);
        abort_unless($pricing->agency_id === $agency->id, 403);

        $pricing->update($request->validated());
        $this->syncAgencyCostRange($agency);

        return back()->with('status', 'pricing-updated');
    }

    public function destroy(Request $request, AgencyPricing $pricing): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        $this->authorize('update', $agency);
        abort_unless($pricing->agency_id === $agency->id, 403);

        $pricing->delete();
        $this->syncAgencyCostRange($agency);

        return back()->with('status', 'pricing-deleted');
    }

    /**
     * Keeps agencies.min_monthly_cost / max_monthly_cost (used in public
     * search filtering) in sync with the pricing tiers the owner maintains,
     * so this denormalized range never drifts from the source rows.
     */
    private function syncAgencyCostRange($agency): void
    {
        $agency->update([
            'min_monthly_cost' => $agency->pricing()->min('monthly_price'),
            'max_monthly_cost' => $agency->pricing()->max('monthly_price'),
        ]);
    }
}
