<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\Billing\CouponService;
use App\Services\Billing\RefundService;
use App\Services\Billing\StripeGatewayException;
use App\Services\Billing\SubscriptionService;
use App\Services\Agency\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
        private readonly CouponService $coupons,
        private readonly PlanLimitService $planLimits,
        private readonly RefundService $refunds,
    ) {}

    public function index(Request $request): View
    {
        $agency = $request->user()->currentAgency();
        abort_unless($request->user()->can('billing.manage_own') || $request->user()->can('billing.manage_all'), 403);

        $subscription = $agency->subscription()->with('plan')->first();
        $plans = Plan::active()->with('features.feature')->get();
        $invoices = $agency->invoices()->latest()->paginate(10, ['*'], 'invoices_page');
        $paymentMethods = $agency->paymentMethods()->get();
        $canFeature = $this->planLimits->canEnableFeaturedListing($agency);

        // Payment History (Phase 16 verification) — individual payment
        // *attempts* across every invoice, distinct from Billing History
        // above (which lists invoices, one row regardless of how many
        // attempts it took to pay one). A retried invoice shows every
        // attempt here.
        $payments = Payment::whereHas('invoice', fn ($q) => $q->where('agency_id', $agency->id))
            ->with('invoice')->latest()->paginate(10, ['*'], 'payments_page');

        return view('agency.billing.index', compact('agency', 'subscription', 'plans', 'invoices', 'paymentMethods', 'canFeature', 'payments'));
    }

    /**
     * Starts a Stripe Checkout session for moving onto a paid plan (or
     * switching billing cycle) — actual activation happens via the
     * checkout.session.completed webhook once payment is confirmed,
     * not here.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        abort_unless($request->user()->can('billing.manage_own') || $request->user()->can('billing.manage_all'), 403);

        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $coupon = null;
        if ($request->filled('coupon_code')) {
            $coupon = $this->coupons->validate($request->coupon_code, $plan);
            if (!$coupon) {
                return back()->withErrors(['coupon_code' => 'This coupon code is invalid, expired, or not applicable to this plan.']);
            }
        }

        try {
            $session = $this->subscriptions->startCheckout($agency, $plan, $request->billing_cycle, $coupon);
        } catch (StripeGatewayException $e) {
            return $this->stripeFailureRedirect($e, 'starting checkout');
        }

        return redirect($session['url'] ?? route('agency.billing.index'));
    }

    public function checkoutSuccess(Request $request): View
    {
        $agency = $request->user()->currentAgency();

        return view('agency.billing.checkout-success', compact('agency'));
    }

    public function changePlan(Request $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        abort_unless($request->user()->can('billing.manage_own') || $request->user()->can('billing.manage_all'), 403);

        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $newPlan = Plan::findOrFail($request->plan_id);
        $currentSubscription = $agency->subscription;

        if (!$currentSubscription || str_starts_with((string) $currentSubscription->stripe_id, 'local_')) {
            return back()->withErrors(['plan' => 'You need to complete checkout to start a paid subscription before you can change plans.']);
        }

        try {
            $this->subscriptions->changePlan($agency, $newPlan, $request->billing_cycle, $request->user());
        } catch (StripeGatewayException $e) {
            return $this->stripeFailureRedirect($e, 'changing your plan');
        }

        return back()->with('status', 'plan-changed');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        abort_unless($request->user()->can('billing.manage_own') || $request->user()->can('billing.manage_all'), 403);

        try {
            $this->subscriptions->cancel($agency, $request->user());
        } catch (StripeGatewayException $e) {
            return $this->stripeFailureRedirect($e, 'canceling your subscription');
        }

        return back()->with('status', 'subscription-canceled');
    }

    public function resume(Request $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        abort_unless($request->user()->can('billing.manage_own') || $request->user()->can('billing.manage_all'), 403);

        try {
            $this->subscriptions->resume($agency, $request->user());
        } catch (StripeGatewayException $e) {
            return $this->stripeFailureRedirect($e, 'resuming your subscription');
        }

        return back()->with('status', 'subscription-resumed');
    }

    public function downgradeToFree(Request $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        abort_unless($request->user()->can('billing.manage_own') || $request->user()->can('billing.manage_all'), 403);

        try {
            $this->subscriptions->downgradeToFree($agency, $request->user());
        } catch (StripeGatewayException $e) {
            return $this->stripeFailureRedirect($e, 'downgrading your plan');
        }

        return back()->with('status', 'downgraded-to-free');
    }

    public function invoiceShow(Request $request, Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'payments', 'agency']);

        return view('agency.billing.invoice-show', compact('invoice'));
    }

    public function invoiceDownload(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        abort_unless($invoice->pdf_path, 404, 'This invoice does not have a generated PDF yet.');

        return \Illuminate\Support\Facades\Storage::disk('public')->download($invoice->pdf_path, $invoice->invoice_number . '.pdf');
    }

    public function removePaymentMethod(Request $request, \App\Models\PaymentMethod $paymentMethod): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        abort_unless($paymentMethod->agency_id === $agency->id, 403);

        activity()->causedBy($request->user())->performedOn($agency)->log('Payment method removed: ' . $paymentMethod->display_name);
        $paymentMethod->delete();

        return back()->with('status', 'payment-method-removed');
    }

    public function requestRefund(Request $request, Payment $payment): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        abort_unless($payment->invoice->agency_id === $agency->id, 403);

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->refunds->request($payment, (float) $request->amount, $request->reason, $request->user());

        return back()->with('status', 'refund-requested');
    }

    /**
     * Every Stripe-calling action funnels a failure through here rather
     * than letting StripeGatewayException reach the default handler as
     * a raw 500 with Stripe's own error text exposed to the agency
     * owner. The real exception (e.g. "You did not provide an API
     * key" when STRIPE_SECRET isn't set — see Admin > Stripe Settings)
     * is logged for an admin to diagnose; a 401/authentication_error
     * specifically gets a distinct, more accurate message than a
     * generic payment failure, since it's a configuration problem, not
     * something the agency owner did wrong or can retry their way out of.
     */
    private function stripeFailureRedirect(StripeGatewayException $e, string $action): RedirectResponse
    {
        Log::error("Stripe error while {$action}: " . $e->getMessage(), [
            'stripe_error_type' => $e->stripeErrorType,
            'http_status' => $e->httpStatus,
        ]);

        $message = $e->httpStatus === 401
            ? 'Billing is not yet fully configured on this site. Please contact support.'
            : 'We couldn\'t complete that billing action right now. Please try again shortly, or contact support if the problem continues.';

        return back()->withErrors(['stripe' => $message]);
    }
}
