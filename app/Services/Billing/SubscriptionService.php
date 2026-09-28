<?php

namespace App\Services\Billing;

use App\Models\Agency;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Subscription lifecycle: starting a real paid subscription via Stripe
 * Checkout, upgrading/downgrading an existing one, cancel/resume, and
 * history. Every Agency already has a Subscription row from
 * AgencyProvisioningService (the Free plan, with a "local_<uuid>"
 * placeholder stripe_id) — starting a real paid plan here REPLACES
 * that same row's plan/stripe fields rather than creating a second
 * row, matching how Stripe itself treats a plan change as an update to
 * the existing subscription object, not a new one.
 */
class SubscriptionService
{
    public function __construct(
        private readonly StripeGateway $stripe,
    ) {}

    /**
     * Creates a Stripe Checkout Session for starting (or switching to)
     * a paid plan. Returns the session array (including its `url`) for
     * the controller to redirect the agency owner to. Does NOT touch
     * the local subscription row yet — that happens when the
     * checkout.session.completed webhook arrives, since the payment
     * isn't confirmed until then.
     */
    public function startCheckout(Agency $agency, Plan $plan, string $billingCycle, ?Coupon $coupon = null): array
    {
        $priceId = $billingCycle === 'yearly' ? $plan->stripe_price_id_yearly : $plan->stripe_price_id_monthly;

        $params = [
            'mode' => 'subscription',
            'customer_email' => $agency->user->email,
            'line_items[0][price]' => $priceId,
            'line_items[0][quantity]' => '1',
            'success_url' => route('agency.billing.checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('agency.billing.index'),
            'metadata[agency_id]' => (string) $agency->id,
            'metadata[plan_id]' => (string) $plan->id,
            'metadata[billing_cycle]' => $billingCycle,
        ];

        if ($plan->trial_days) {
            $params['subscription_data[trial_period_days]'] = (string) $plan->trial_days;
        }

        if ($coupon?->stripe_coupon_id) {
            $params['discounts[0][coupon]'] = $coupon->stripe_coupon_id;
        }

        return $this->stripe->createCheckoutSession($params);
    }

    /**
     * Activates the agency's subscription after a successful checkout
     * — called from the webhook handler, not the redirect-back
     * controller action, since the webhook is the authoritative signal
     * that payment actually succeeded (the success_url redirect alone
     * can't be trusted — a user can navigate there without paying).
     */
    public function activateFromStripe(Agency $agency, Plan $plan, string $stripeSubscriptionId, string $stripeStatus, string $stripePrice, ?Carbon $trialEndsAt = null): Subscription
    {
        $subscription = $agency->subscription;

        $data = [
            'plan_id' => $plan->id,
            'type' => 'default',
            'stripe_id' => $stripeSubscriptionId,
            'stripe_status' => $stripeStatus,
            'stripe_price' => $stripePrice,
            'trial_ends_at' => $trialEndsAt,
            'ends_at' => null,
        ];

        if ($subscription) {
            $subscription->update($data);
        } else {
            $subscription = Subscription::create(['agency_id' => $agency->id, ...$data]);
        }

        return $subscription->fresh();
    }

    /**
     * Every checkout this app creates uses mode=subscription (see
     * startCheckout()) — and Stripe Checkout Sessions in that mode do
     * NOT populate a top-level payment_intent field the way mode=payment
     * sessions do. The actual PaymentIntent lives on the subscription's
     * latest_invoice instead: Session -> subscription ID -> Subscription's
     * latest_invoice ID -> that Invoice's payment_intent field. Without
     * this resolution, stripe_payment_intent_id was being stored as
     * null on every real subscription checkout — which doesn't just
     * affect display (payment_methods never synced), it silently
     * broke real Stripe refunds too: RefundService::approve() only
     * calls Stripe's refund API when a payment has a
     * stripe_payment_intent_id, so a null one meant the LOCAL refund
     * record would update but no money would ever actually move.
     *
     * The mode=payment fallback (checking session['payment_intent']
     * first) is kept for robustness even though this app never
     * creates payment-mode sessions today.
     */
    /**
     * Retrieves the real Stripe Subscription created by a checkout and
     * resolves everything handleCheckoutCompleted() needs from it in
     * one round-trip.
     *
     * Trial detection previously checked
     * session['subscription_data']['trial_end'] — but that key is only
     * ever present in the PARAMETERS used to CREATE a checkout session,
     * never in the session object Stripe actually sends back on the
     * webhook. That check silently always returned null, meaning every
     * trial checkout was treated as a full-price non-trial charge: the
     * local invoice showed the full plan price "Paid" immediately even
     * though Stripe hadn't charged anything yet (nothing is charged
     * during a trial — the real charge happens later, via a separate
     * invoice.paid webhook when the trial ends). Trial status is now
     * read from the real Subscription object's own `status` field
     * ('trialing' vs 'active'), which is authoritative.
     *
     * The payment method is resolved from the subscription's
     * default_payment_method — populated by Stripe as soon as Checkout
     * collects a card, REGARDLESS of trial status — rather than
     * through the invoice's payment_intent, which doesn't exist for a
     * $0 trial invoice (no PaymentIntent is created when nothing is
     * charged). This was the deeper reason the payment-method sync
     * kept coming back empty even after the invoice/payment_intent
     * resolution fix: that fix was correct for a real, immediate
     * charge, but every plan in this app has a trial, so checkout
     * almost never takes that path in practice.
     *
     * payment_intent_id is only resolved (and only meaningful) for a
     * non-trialing subscription, where a real charge actually happened
     * at checkout time.
     *
     * Every failure path here returns defensively rather than
     * throwing, so a Stripe hiccup degrades to "activate the
     * subscription with best-effort detail" instead of failing the
     * whole webhook.
     */
    public function resolveCheckoutOutcome(array $session): array
    {
        $result = [
            'is_trialing' => false,
            'trial_ends_at' => null,
            'payment_intent_id' => null,
            'default_payment_method_id' => null,
        ];

        if (empty($session['subscription'])) {
            return $result;
        }

        try {
            $subscription = $this->stripe->retrieveSubscription($session['subscription']);
        } catch (StripeGatewayException $e) {
            Log::warning('Could not retrieve subscription ' . $session['subscription'] . ' while resolving checkout outcome: ' . $e->getMessage());

            return $result;
        }

        $result['is_trialing'] = ($subscription['status'] ?? null) === 'trialing';
        $result['trial_ends_at'] = !empty($subscription['trial_end']) ? Carbon::createFromTimestamp($subscription['trial_end']) : null;
        $result['default_payment_method_id'] = $subscription['default_payment_method'] ?? null;

        if (!$result['is_trialing']) {
            if (!empty($session['payment_intent'])) {
                $result['payment_intent_id'] = $session['payment_intent'];
            } elseif (!empty($subscription['latest_invoice'])) {
                try {
                    $invoice = $this->stripe->retrieveInvoice($subscription['latest_invoice']);
                    $result['payment_intent_id'] = $invoice['payment_intent'] ?? null;
                } catch (StripeGatewayException $e) {
                    Log::warning('Could not resolve payment_intent from invoice: ' . $e->getMessage());
                }
            }
        }

        return $result;
    }

    /**
     * Pulls a card's details into the local payment_methods table, so
     * the Billing page's "Payment Methods" panel reflects reality
     * instead of always saying "No payment methods on file yet".
     * Takes a Stripe payment method ID directly (resolved by the
     * caller — see resolveCheckoutOutcome()'s default_payment_method,
     * which is populated as soon as Checkout collects a card
     * regardless of trial status, unlike a payment_intent which only
     * exists once a real charge has happened).
     *
     * Deliberately non-fatal: the subscription itself has already
     * activated successfully by the time this runs, and a sync
     * failure here (e.g. a transient Stripe hiccup) shouldn't undo
     * that or surface as an error to the agency — it's logged for
     * visibility and the next successful payment will sync it anyway.
     */
    public function syncPaymentMethodFromStripe(Agency $agency, ?string $paymentMethodId): void
    {
        if (!$paymentMethodId) {
            return;
        }

        try {
            $paymentMethod = $this->stripe->retrievePaymentMethod($paymentMethodId);
            $card = $paymentMethod['card'] ?? null;
            if (!$card) {
                return;
            }

            $agency->paymentMethods()->update(['is_default' => false]);
            $agency->paymentMethods()->updateOrCreate(
                ['stripe_payment_method_id' => $paymentMethodId],
                [
                    'brand' => $card['brand'] ?? null,
                    'last_four' => $card['last4'] ?? null,
                    'exp_month' => $card['exp_month'] ?? null,
                    'exp_year' => $card['exp_year'] ?? null,
                    'is_default' => true,
                ]
            );
        } catch (StripeGatewayException $e) {
            Log::warning('Could not sync payment method from Stripe for agency ' . $agency->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Upgrade or downgrade to a different plan. Only meaningful for an
     * agency already on a real Stripe subscription (not the Free
     * plan's local placeholder) — moving off Free for the first time
     * goes through startCheckout() instead, since Stripe needs a real
     * checkout to collect payment details.
     */
    public function changePlan(Agency $agency, Plan $newPlan, string $billingCycle, ?User $actor = null): Subscription
    {
        $subscription = $agency->subscription;
        abort_if(!$subscription, 404, 'Agency has no subscription to change.');

        $priceId = $billingCycle === 'yearly' ? $newPlan->stripe_price_id_yearly : $newPlan->stripe_price_id_monthly;

        if (!$this->isLocalPlaceholder($subscription)) {
            $this->stripe->updateSubscription($subscription->stripe_id, [
                'items[0][price]' => $priceId,
                'proration_behavior' => 'create_prorations',
            ]);
        }

        $subscription->update([
            'plan_id' => $newPlan->id,
            'stripe_price' => $priceId,
        ]);

        activity()->causedBy($actor)->performedOn($agency)
            ->log('Subscription plan changed to ' . $newPlan->name . ' (' . $billingCycle . ')');

        return $subscription->fresh();
    }

    public function cancel(Agency $agency, ?User $actor = null, bool $immediately = false): Subscription
    {
        $subscription = $agency->subscription;
        abort_if(!$subscription, 404, 'Agency has no subscription to cancel.');

        if (!$this->isLocalPlaceholder($subscription)) {
            $this->stripe->cancelSubscription($subscription->stripe_id, !$immediately);
        }

        $subscription->update([
            'ends_at' => $immediately ? now() : now()->endOfMonth(),
            'stripe_status' => $immediately ? 'canceled' : $subscription->stripe_status,
        ]);

        activity()->causedBy($actor)->performedOn($agency)->log('Subscription canceled' . ($immediately ? ' immediately' : ' at period end'));

        return $subscription->fresh();
    }

    public function resume(Agency $agency, ?User $actor = null): Subscription
    {
        $subscription = $agency->subscription;
        abort_if(!$subscription, 404, 'Agency has no subscription to resume.');
        abort_if(!$subscription->ends_at, 422, 'Subscription is not scheduled to cancel.');

        if (!$this->isLocalPlaceholder($subscription)) {
            $this->stripe->resumeSubscription($subscription->stripe_id);
        }

        $subscription->update(['ends_at' => null, 'stripe_status' => 'active']);

        activity()->causedBy($actor)->performedOn($agency)->log('Subscription resumed');

        return $subscription->fresh();
    }

    /**
     * Downgrades an agency straight to the Free plan (used both as an
     * explicit "downgrade to Free" action and as the effective result
     * once a canceled paid subscription's period actually ends).
     */
    /**
     * @param  bool  $skipStripeCall  true when called FROM a
     *   customer.subscription.deleted webhook — Stripe has already
     *   deleted the subscription on their side, so issuing another
     *   cancelSubscription() call would be redundant (and, for an
     *   already-deleted Stripe subscription, simply fails). The
     *   agency-initiated explicit-downgrade path still needs the real
     *   Stripe call, so this defaults to false.
     */
    public function downgradeToFree(Agency $agency, ?User $actor = null, bool $skipStripeCall = false): Subscription
    {
        $subscription = $agency->subscription;
        $freePlan = Plan::where('code', 'free')->firstOrFail();

        if ($subscription && !$skipStripeCall && !$this->isLocalPlaceholder($subscription)) {
            $this->stripe->cancelSubscription($subscription->stripe_id, false);
        }

        if ($subscription) {
            $subscription->update([
                'plan_id' => $freePlan->id,
                'stripe_id' => 'local_' . Str::uuid(),
                'stripe_status' => 'active',
                'stripe_price' => null,
                'trial_ends_at' => null,
                'ends_at' => null,
            ]);
        } else {
            $subscription = Subscription::create([
                'agency_id' => $agency->id, 'plan_id' => $freePlan->id, 'type' => 'default',
                'stripe_id' => 'local_' . Str::uuid(), 'stripe_status' => 'active',
            ]);
        }

        activity()->causedBy($actor)->performedOn($agency)->log('Subscription downgraded to Free plan');

        return $subscription->fresh();
    }

    private function isLocalPlaceholder(Subscription $subscription): bool
    {
        return str_starts_with((string) $subscription->stripe_id, 'local_');
    }
}
