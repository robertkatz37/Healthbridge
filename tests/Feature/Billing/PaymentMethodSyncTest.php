<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use Illuminate\Support\Facades\Http;

/**
 * Every paid plan in this app (Premium, Professional, Enterprise) has
 * a trial_days value, so in real-world usage EVERY checkout is a
 * trialing checkout — this is the normal path, not an edge case. Two
 * real, related bugs are covered here:
 *
 * 1. Trial detection previously checked a webhook payload field
 *    (session.subscription_data.trial_end) that Stripe never actually
 *    sends — it's only a parameter used to CREATE a session, not part
 *    of what comes back. Every trial checkout was silently treated as
 *    a full-price non-trial charge, recording the entire plan price
 *    as "Paid" immediately even though Stripe hadn't charged anything
 *    (nothing is charged during a trial — the real charge happens
 *    later via invoice.paid when the trial ends).
 * 2. The payment method sync depended on a payment_intent existing,
 *    which a $0 trial invoice never has (no PaymentIntent is created
 *    for a zero-amount charge) — so it silently never populated,
 *    exactly matching what was reported: card charged, subscription
 *    active, but Payment Methods staying empty forever.
 *
 * Every payload here reflects the REAL shape of a Stripe subscription
 * object for each case (status: 'trialing' vs 'active',
 * default_payment_method populated in both cases since Stripe collects
 * the card upfront regardless of trial status).
 */
function fakeSubscriptionResponse(string $subscriptionId, string $status, ?int $trialEnd, ?string $defaultPaymentMethodId, ?string $latestInvoiceId = null): void
{
    Http::fake([
        "api.stripe.com/v1/subscriptions/{$subscriptionId}" => Http::response([
            'id' => $subscriptionId, 'status' => $status, 'trial_end' => $trialEnd,
            'default_payment_method' => $defaultPaymentMethodId, 'latest_invoice' => $latestInvoiceId,
        ], 200),
    ]);
}

beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, ['agency_category_id' => $category->id, 'name' => 'PM Sync Test Agency']);
    $this->agency->update(['status' => 'published']);
    $this->owner->update(['current_agency_id' => $this->agency->id]);
    $this->plan = Plan::where('code', 'premium')->first();
});

function sendCheckoutCompletedWebhook(string $secret, string $sessionId, string $subscriptionId, int $agencyId, int $planId, string $billingCycle = 'monthly') {
    $payload = json_encode([
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => $sessionId, 'subscription' => $subscriptionId,
            'metadata' => ['agency_id' => (string) $agencyId, 'plan_id' => (string) $planId, 'billing_cycle' => $billingCycle],
        ]],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    return test()->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}", 'CONTENT_TYPE' => 'application/json'], $payload);
}

test('a TRIALING checkout (the normal case — every paid plan has a trial) creates a $0 invoice, not a false full-price charge, and still syncs the card', function () {
    $secret = 'whsec_trial_test';
    config(['services.stripe.webhook_secret' => $secret]);
    $trialEnd = now()->addDays(14)->timestamp;

    fakeSubscriptionResponse('sub_trial_1', 'trialing', $trialEnd, 'pm_trial_card');
    Http::fake(['api.stripe.com/v1/subscriptions/sub_trial_1' => Http::response(['id' => 'sub_trial_1', 'status' => 'trialing', 'trial_end' => $trialEnd, 'default_payment_method' => 'pm_trial_card', 'latest_invoice' => null], 200),
        'api.stripe.com/v1/payment_methods/pm_trial_card' => Http::response(['id' => 'pm_trial_card', 'card' => ['brand' => 'visa', 'last4' => '4242', 'exp_month' => 12, 'exp_year' => 2030]], 200)]);

    $response = sendCheckoutCompletedWebhook($secret, 'cs_trial_1', 'sub_trial_1', $this->agency->id, $this->plan->id);
    $response->assertOk();

    $subscription = $this->agency->fresh()->subscription;
    expect($subscription->plan->code)->toBe('premium');
    expect($subscription->stripe_status)->toBe('trialing');
    expect($subscription->trial_ends_at)->not->toBeNull();

    $invoice = $this->agency->invoices()->latest()->first();
    expect((float) $invoice->total_amount)->toBe(0.0);
    expect($invoice->status->value)->toBe('paid');
    expect($invoice->description ?? $invoice->items->first()->description)->toContain('Trial Started');

    $paymentMethod = $this->agency->fresh()->paymentMethods()->first();
    expect($paymentMethod)->not->toBeNull();
    expect($paymentMethod->brand)->toBe('visa');
    expect($paymentMethod->last_four)->toBe('4242');

    $billingPage = $this->actingAs($this->owner)->get(route('agency.billing.index'));
    $billingPage->assertOk();
    $billingPage->assertSee('Visa');
    $billingPage->assertSee('4242');
    $billingPage->assertDontSee('No payment methods on file yet');
});

test('a trialing checkout does NOT send a PaymentReceived notification, since nothing was actually charged', function () {
    Illuminate\Support\Facades\Notification::fake();
    $secret = 'whsec_trial_notif_test';
    config(['services.stripe.webhook_secret' => $secret]);

    Http::fake(['api.stripe.com/v1/subscriptions/sub_trial_notif_1' => Http::response(['id' => 'sub_trial_notif_1', 'status' => 'trialing', 'trial_end' => now()->addDays(14)->timestamp, 'default_payment_method' => null, 'latest_invoice' => null], 200)]);

    sendCheckoutCompletedWebhook($secret, 'cs_trial_notif_1', 'sub_trial_notif_1', $this->agency->id, $this->plan->id);

    Illuminate\Support\Facades\Notification::assertNotSentTo($this->owner, \App\Notifications\Billing\PaymentReceived::class);
});

test('a NON-TRIALING checkout (e.g. a plan with no trial configured) records the real charge and resolves payment_intent via the invoice chain', function () {
    $secret = 'whsec_active_test';
    config(['services.stripe.webhook_secret' => $secret]);

    Http::fake([
        'api.stripe.com/v1/subscriptions/sub_active_1' => Http::response(['id' => 'sub_active_1', 'status' => 'active', 'trial_end' => null, 'default_payment_method' => 'pm_active_card', 'latest_invoice' => 'in_active_1'], 200),
        'api.stripe.com/v1/invoices/in_active_1' => Http::response(['id' => 'in_active_1', 'payment_intent' => 'pi_active_1'], 200),
        'api.stripe.com/v1/payment_methods/pm_active_card' => Http::response(['id' => 'pm_active_card', 'card' => ['brand' => 'mastercard', 'last4' => '5555', 'exp_month' => 8, 'exp_year' => 2029]], 200),
    ]);

    $response = sendCheckoutCompletedWebhook($secret, 'cs_active_1', 'sub_active_1', $this->agency->id, $this->plan->id);
    $response->assertOk();

    $subscription = $this->agency->fresh()->subscription;
    expect($subscription->stripe_status)->toBe('active');
    expect($subscription->trial_ends_at)->toBeNull();

    $invoice = $this->agency->invoices()->latest()->first();
    expect((float) $invoice->total_amount)->toBe(99.0);
    expect($invoice->status->value)->toBe('paid');

    $payment = $invoice->payments()->first();
    expect($payment->stripe_payment_intent_id)->toBe('pi_active_1');

    $paymentMethod = $this->agency->fresh()->paymentMethods()->first();
    expect($paymentMethod->last_four)->toBe('5555');
});

test('a non-trialing checkout DOES send a PaymentReceived notification, since a real charge happened', function () {
    Illuminate\Support\Facades\Notification::fake();
    $secret = 'whsec_active_notif_test';
    config(['services.stripe.webhook_secret' => $secret]);

    Http::fake(['api.stripe.com/v1/subscriptions/sub_active_notif_1' => Http::response(['id' => 'sub_active_notif_1', 'status' => 'active', 'trial_end' => null, 'default_payment_method' => null, 'latest_invoice' => null], 200)]);

    sendCheckoutCompletedWebhook($secret, 'cs_active_notif_1', 'sub_active_notif_1', $this->agency->id, $this->plan->id);

    Illuminate\Support\Facades\Notification::assertSentTo($this->owner, \App\Notifications\Billing\PaymentReceived::class);
});

test('a Stripe failure while retrieving the subscription does not undo the already-activated subscription, and simply skips detail resolution', function () {
    $secret = 'whsec_fail_test';
    config(['services.stripe.webhook_secret' => $secret]);

    Http::fake(['api.stripe.com/v1/subscriptions/sub_fail_1' => Http::response(['error' => ['message' => 'Not found']], 404)]);

    $response = sendCheckoutCompletedWebhook($secret, 'cs_fail_1', 'sub_fail_1', $this->agency->id, $this->plan->id);

    $response->assertOk();
    expect($this->agency->fresh()->subscription->plan->code)->toBe('premium');
    expect($this->agency->fresh()->paymentMethods()->count())->toBe(0);
});

test('a second checkout replaces the previous default payment method rather than adding a duplicate default', function () {
    $this->agency->paymentMethods()->create(['stripe_payment_method_id' => 'pm_old', 'brand' => 'amex', 'last_four' => '0000', 'is_default' => true]);

    $secret = 'whsec_second_test';
    config(['services.stripe.webhook_secret' => $secret]);

    Http::fake([
        'api.stripe.com/v1/subscriptions/sub_second_1' => Http::response(['id' => 'sub_second_1', 'status' => 'trialing', 'trial_end' => now()->addDays(14)->timestamp, 'default_payment_method' => 'pm_new', 'latest_invoice' => null], 200),
        'api.stripe.com/v1/payment_methods/pm_new' => Http::response(['id' => 'pm_new', 'card' => ['brand' => 'visa', 'last4' => '1111', 'exp_month' => 6, 'exp_year' => 2031]], 200),
    ]);

    sendCheckoutCompletedWebhook($secret, 'cs_second_1', 'sub_second_1', $this->agency->id, $this->plan->id);

    expect($this->agency->fresh()->paymentMethods()->count())->toBe(2);
    expect($this->agency->fresh()->paymentMethods()->where('is_default', true)->count())->toBe(1);
    expect($this->agency->fresh()->defaultPaymentMethod->last_four)->toBe('1111');
});
