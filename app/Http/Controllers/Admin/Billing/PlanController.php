<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('billing.manage_all'), 403);

        $plans = Plan::with('features.feature')->withCount(['subscriptions' => fn ($q) => $q->where('stripe_status', 'active')])
            ->orderBy('sort_order')->get();

        return view('admin.billing.plans.index', compact('plans'));
    }

    public function edit(Request $request, Plan $plan): View
    {
        abort_unless($request->user()->can('billing.manage_all'), 403);

        $plan->load('features.feature');

        return view('admin.billing.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($request->user()->can('billing.manage_all'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'stripe_price_id_monthly' => ['nullable', 'string', 'max:255'],
            'stripe_price_id_yearly' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'features' => ['array'],
        ]);

        $plan->update([
            'name' => $data['name'],
            'price_monthly' => $data['price_monthly'],
            'price_yearly' => $data['price_yearly'],
            'trial_days' => $data['trial_days'] ?: null,
            'stripe_price_id_monthly' => $data['stripe_price_id_monthly'],
            'stripe_price_id_yearly' => $data['stripe_price_id_yearly'],
            'is_active' => $request->boolean('is_active'),
        ]);

        foreach ($request->input('features', []) as $featureId => $value) {
            $plan->features()->updateOrCreate(['subscription_feature_id' => $featureId], ['value' => $value]);
        }

        activity()->causedBy($request->user())->performedOn($plan)->log('Plan updated: ' . $plan->name);

        return redirect()->route('admin.billing.plans.index')->with('status', 'plan-updated');
    }
}
