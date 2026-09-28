<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Plan;
use App\Services\Billing\CouponService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $coupons,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('manage', Coupon::class);

        $coupons = Coupon::with('plan')->latest()->paginate(20);
        $plans = Plan::orderBy('sort_order')->get();

        return view('admin.billing.coupons.index', compact('coupons', 'plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', Coupon::class);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code', 'alpha_dash'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'applies_to' => ['required', 'in:all,subscription,featured_listing'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = true;

        $this->coupons->create($data, $request->user());

        return back()->with('status', 'coupon-created');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->authorize('manage', Coupon::class);

        $request->validate(['is_active' => ['sometimes', 'boolean']]);

        $coupon->update(['is_active' => $request->boolean('is_active')]);

        activity()->causedBy($request->user())->performedOn($coupon)->log($coupon->is_active ? 'Coupon activated' : 'Coupon deactivated');

        return back()->with('status', 'coupon-updated');
    }

    public function destroy(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->authorize('manage', Coupon::class);

        activity()->causedBy($request->user())->performedOn($coupon)->log('Coupon deleted: ' . $coupon->code);
        $coupon->delete();

        return back()->with('status', 'coupon-deleted');
    }
}
