<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Services\Agency\PlanLimitService;
use App\Services\Billing\StripeGateway;
use App\Services\Billing\StripeGatewayException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Featured Listing has two paths to being enabled: included free on a
 * plan that already grants it (Professional/Enterprise —
 * PlanLimitService::canEnableFeaturedListing), or purchased as a
 * standalone 30-day add-on by an agency on a lower plan. This
 * controller is specifically the purchase path; the plan-included
 * toggle stays in Agency\SettingsController.
 *
 * Routes through a real one-time Stripe Checkout Session (mode=payment)
 * — this previously recorded the invoice as "Paid" locally without
 * ever actually charging anything through Stripe, which is a real
 * billing-integrity problem, not a minor gap. Fulfillment (marking the
 * invoice paid, setting is_featured/featured_until) happens from the
 * checkout.session.completed webhook once Stripe confirms payment,
 * exactly like a subscription purchase — never from this
 * redirect-initiating action itself.
 */
class FeaturedListingController extends Controller
{
    public const ADDON_PRICE = 49.00;

    public function __construct(
        private readonly PlanLimitService $planLimits,
        private readonly StripeGateway $stripe,
    ) {}

    public function purchase(Request $request): RedirectResponse
    {
        $agency = $request->user()->currentAgency();
        abort_unless($request->user()->can('billing.manage_own') || $request->user()->can('billing.manage_all'), 403);

        if ($this->planLimits->canEnableFeaturedListing($agency)) {
            return back()->withErrors(['plan' => 'Your current plan already includes Featured Listing — enable it from Settings at no extra charge.']);
        }

        if ($agency->is_featured) {
            return back()->withErrors(['plan' => 'Featured Listing is already active for your agency.']);
        }

        try {
            $session = $this->stripe->createCheckoutSession([
                'mode' => 'payment',
                'customer_email' => $agency->user->email,
                'line_items[0][price_data][currency]' => 'usd',
                'line_items[0][price_data][unit_amount]' => (string) (int) round(self::ADDON_PRICE * 100),
                'line_items[0][price_data][product_data][name]' => 'Featured Listing — 30 days',
                'line_items[0][quantity]' => '1',
                'success_url' => route('agency.billing.checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('agency.billing.index'),
                'metadata[agency_id]' => (string) $agency->id,
                'metadata[purchase_type]' => 'featured_listing',
            ]);
        } catch (StripeGatewayException $e) {
            Log::error('Stripe error while starting Featured Listing checkout: ' . $e->getMessage(), [
                'stripe_error_type' => $e->stripeErrorType, 'http_status' => $e->httpStatus,
            ]);
            $message = $e->httpStatus === 401
                ? 'Billing is not yet fully configured on this site. Please contact support.'
                : 'We couldn\'t start checkout right now. Please try again shortly, or contact support if the problem continues.';

            return back()->withErrors(['stripe' => $message]);
        }

        return redirect($session['url'] ?? route('agency.billing.index'));
    }
}
