<?php

namespace App\Http\Controllers\Billing;

use App\Enums\InvoiceType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Agency\FeaturedListingController;
use App\Models\Agency;
use App\Models\Invoice;
use App\Models\Plan;
use App\Notifications\Billing\PaymentFailed;
use App\Notifications\Billing\PaymentReceived;
use App\Notifications\Billing\SubscriptionRenewed;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\StripeGateway;
use App\Services\Billing\StripeGatewayException;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Every state change Stripe knows about but our database doesn't yet
 * arrives here — this is the ONLY place that actually activates a
 * subscription, marks an invoice paid/failed, or reacts to a
 * cancellation, precisely because it's the only signal that can be
 * trusted: a browser redirect back from Checkout means the customer's
 * browser got redirected, not that payment succeeded.
 */
class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeGateway $stripe,
        private readonly SubscriptionService $subscriptions,
        private readonly InvoiceService $invoices,
        private readonly PaymentService $payments,
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');

        if (!$this->stripe->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Stripe webhook rejected: invalid signature.');

            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);
        $type = $event['type'] ?? null;
        $object = $event['data']['object'] ?? [];

        match ($type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($object),
            'invoice.paid' => $this->handleInvoicePaid($object),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($object),
            default => Log::info('Stripe webhook received, no handler for type: ' . ($type ?? 'unknown')),
        };

        return response('OK', 200);
    }

    private function handleCheckoutCompleted(array $session): void
    {
        // Stripe's own documentation says a webhook event can be
        // delivered more than once (network retries on their end, or
        // a slow response causing a retry before our 200 is received)
        // — without this check, a redelivered event would silently
        // create a second invoice and a second real charge record for
        // the same purchase. Checked first, before any branching,
        // since it applies to every kind of checkout this app creates.
        if (!empty($session['id']) && Invoice::where('stripe_checkout_session_id', $session['id'])->exists()) {
            Log::info('Stripe checkout.session.completed already processed, skipping duplicate delivery.', ['session_id' => $session['id']]);

            return;
        }

        $purchaseType = $session['metadata']['purchase_type'] ?? 'subscription';

        if ($purchaseType === 'featured_listing') {
            $this->handleFeaturedListingCheckoutCompleted($session);

            return;
        }

        $agencyId = $session['metadata']['agency_id'] ?? null;
        $planId = $session['metadata']['plan_id'] ?? null;
        $billingCycle = $session['metadata']['billing_cycle'] ?? 'monthly';

        $agency = Agency::find($agencyId);
        $plan = Plan::find($planId);

        if (!$agency || !$plan) {
            Log::warning('Stripe checkout.session.completed missing agency/plan metadata.', ['session_id' => $session['id'] ?? null]);

            return;
        }

        $subscriptionId = $session['subscription'] ?? ('local_' . $session['id']);
        $outcome = $this->subscriptions->resolveCheckoutOutcome($session);

        $subscription = $this->subscriptions->activateFromStripe(
            $agency, $plan, $subscriptionId, $outcome['is_trialing'] ? 'trialing' : 'active',
            $billingCycle === 'yearly' ? $plan->stripe_price_id_yearly : $plan->stripe_price_id_monthly,
            $outcome['trial_ends_at']
        );

        // Nothing is actually charged during a trial — Stripe bills the
        // real amount later, via a separate invoice.paid webhook once
        // the trial ends. Recording the full plan price as "Paid" here
        // for a trialing subscription would be simply wrong.
        $amount = $outcome['is_trialing']
            ? 0.0
            : ($billingCycle === 'yearly' ? (float) $plan->price_yearly : (float) $plan->price_monthly);

        $description = $outcome['is_trialing']
            ? $plan->name . ' Plan — Trial Started' . ($outcome['trial_ends_at'] ? ' (first charge ' . $outcome['trial_ends_at']->format('M d, Y') . ')' : '')
            : $plan->name . ' Plan (' . ucfirst($billingCycle) . ')';

        $invoice = $this->invoices->create(
            $agency,
            InvoiceType::Subscription,
            [['description' => $description, 'amount' => $amount]],
        );
        $invoice->update(['stripe_checkout_session_id' => $session['id'] ?? null]);

        if ($amount > 0) {
            $this->payments->recordSuccess($invoice, $amount, 'stripe', $outcome['payment_intent_id']);
        } else {
            $this->invoices->markPaid($invoice);
        }

        $this->subscriptions->syncPaymentMethodFromStripe($agency, $outcome['default_payment_method_id']);

        if ($amount > 0) {
            $agency->user->notify(new PaymentReceived($invoice));
        }

        activity()->performedOn($subscription)->log('Subscription activated via Stripe Checkout: ' . $plan->name . ($outcome['is_trialing'] ? ' (trial)' : ''));
    }

    /**
     * Featured Listing is a one-time (mode=payment) checkout, not a
     * subscription — Stripe DOES populate payment_intent directly on
     * a mode=payment session (unlike mode=subscription, see
     * SubscriptionService::resolveCheckoutOutcome()'s docblock), so
     * no subscription/invoice resolution chain is needed here.
     */
    private function handleFeaturedListingCheckoutCompleted(array $session): void
    {
        $agencyId = $session['metadata']['agency_id'] ?? null;
        $agency = Agency::find($agencyId);

        if (!$agency) {
            Log::warning('Stripe checkout.session.completed (featured_listing) missing agency metadata.', ['session_id' => $session['id'] ?? null]);

            return;
        }

        $amount = !empty($session['amount_total']) ? $session['amount_total'] / 100 : FeaturedListingController::ADDON_PRICE;

        $invoice = $this->invoices->create(
            $agency,
            InvoiceType::FeaturedListing,
            [['description' => 'Featured Listing — 30 days', 'amount' => $amount]],
        );
        $invoice->update(['stripe_checkout_session_id' => $session['id'] ?? null]);

        $paymentIntentId = $session['payment_intent'] ?? null;
        $this->payments->recordSuccess($invoice, $amount, 'stripe', $paymentIntentId);

        $agency->update(['is_featured' => true, 'featured_until' => now()->addDays(30)]);

        if ($paymentIntentId) {
            try {
                $paymentIntent = $this->stripe->retrievePaymentIntent($paymentIntentId);
                $this->subscriptions->syncPaymentMethodFromStripe($agency, $paymentIntent['payment_method'] ?? null);
            } catch (StripeGatewayException $e) {
                Log::warning('Could not sync payment method for Featured Listing purchase: ' . $e->getMessage());
            }
        }

        $agency->user->notify(new PaymentReceived($invoice));

        activity()->performedOn($agency)->log('Featured Listing purchased via Stripe Checkout (30 days)');
    }

    private function handleInvoicePaid(array $stripeInvoice): void
    {
        $invoice = Invoice::where('stripe_invoice_id', $stripeInvoice['id'] ?? null)->first();
        if (!$invoice) {
            return;
        }

        $amount = ($stripeInvoice['amount_paid'] ?? 0) / 100;
        $this->payments->recordSuccess($invoice, $amount, 'stripe', $stripeInvoice['payment_intent'] ?? null);

        $invoice->agency->user->notify(new SubscriptionRenewed($invoice));
    }

    private function handleInvoicePaymentFailed(array $stripeInvoice): void
    {
        $invoice = Invoice::where('stripe_invoice_id', $stripeInvoice['id'] ?? null)->first();
        if (!$invoice) {
            return;
        }

        $amount = ($stripeInvoice['amount_due'] ?? 0) / 100;
        $reason = $stripeInvoice['last_payment_error']['message'] ?? 'Payment declined';
        $this->payments->recordFailure($invoice, $amount, 'stripe', $reason, $stripeInvoice['payment_intent'] ?? null);

        $invoice->agency->user->notify(new PaymentFailed($invoice, $reason));
    }

    private function handleSubscriptionUpdated(array $stripeSubscription): void
    {
        $subscription = \App\Models\Subscription::where('stripe_id', $stripeSubscription['id'] ?? null)->first();
        if (!$subscription) {
            return;
        }

        $subscription->update(['stripe_status' => $stripeSubscription['status'] ?? $subscription->stripe_status]);
    }

    private function handleSubscriptionDeleted(array $stripeSubscription): void
    {
        $subscription = \App\Models\Subscription::where('stripe_id', $stripeSubscription['id'] ?? null)->first();
        if (!$subscription) {
            return;
        }

        $this->subscriptions->downgradeToFree($subscription->agency, skipStripeCall: true);
    }
}
