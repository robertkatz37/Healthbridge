<?php

use App\Enums\InvoiceType;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use App\Services\Billing\InvoiceService;

/**
 * Exercises every Stripe webhook event type the app handles, with a
 * real signed payload through the actual /webhooks/stripe endpoint —
 * not just signature verification in isolation (StripeGatewayTest)
 * and not just checkout.session.completed / invoice.payment_failed
 * (already covered elsewhere). Closes the gap where invoice.paid,
 * customer.subscription.updated, and customer.subscription.deleted
 * had signature-format tests but no test of their actual handler logic.
 */
beforeEach(function () {
    $this->withoutVite();
    $this->secret = 'whsec_test_all_events';
    config(['services.stripe.webhook_secret' => $this->secret]);

    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, ['agency_category_id' => $category->id, 'name' => 'Webhook Test Agency']);
    $this->agency->update(['status' => 'published']);

    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_webhook_1', 'stripe_status' => 'active']);
});

function sendSignedWebhook($secret, $payload) {
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    return test()->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

test('invoice.paid webhook records a successful payment against the matching local invoice', function () {
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Renewal', 'amount' => 99]]);
    $invoice->update(['stripe_invoice_id' => 'in_paid_test_1']);

    $payload = json_encode([
        'type' => 'invoice.paid',
        'data' => ['object' => ['id' => 'in_paid_test_1', 'amount_paid' => 9900, 'payment_intent' => 'pi_paid_1']],
    ]);

    $response = sendSignedWebhook($this->secret, $payload);

    $response->assertOk();
    $freshInvoice = $invoice->fresh();
    expect($freshInvoice->status->value)->toBe('paid');
    expect($freshInvoice->paid_at)->not->toBeNull();
    $payment = $freshInvoice->payments()->latest()->first();
    expect($payment)->not->toBeNull();
    expect((float) $payment->amount)->toBe(99.0);
});

test('invoice.paid webhook for an unknown stripe_invoice_id is safely ignored, not an error', function () {
    $payload = json_encode(['type' => 'invoice.paid', 'data' => ['object' => ['id' => 'in_does_not_exist', 'amount_paid' => 5000]]]);

    $response = sendSignedWebhook($this->secret, $payload);

    $response->assertOk();
});

test('customer.subscription.updated webhook syncs the stripe_status onto the local subscription', function () {
    $payload = json_encode(['type' => 'customer.subscription.updated', 'data' => ['object' => ['id' => 'sub_webhook_1', 'status' => 'past_due']]]);

    $response = sendSignedWebhook($this->secret, $payload);

    $response->assertOk();
    expect($this->agency->subscription->fresh()->stripe_status)->toBe('past_due');
});

test('customer.subscription.deleted webhook downgrades the agency to the Free plan', function () {
    $payload = json_encode(['type' => 'customer.subscription.deleted', 'data' => ['object' => ['id' => 'sub_webhook_1']]]);

    $response = sendSignedWebhook($this->secret, $payload);

    $response->assertOk();
    $subscription = $this->agency->subscription->fresh();
    expect($subscription->plan->code)->toBe('free');
    expect($subscription->stripe_id)->toStartWith('local_');
});

test('an unrecognized webhook event type is accepted (200) but does nothing, rather than erroring', function () {
    $payload = json_encode(['type' => 'charge.dispute.created', 'data' => ['object' => ['id' => 'dp_1']]]);

    $response = sendSignedWebhook($this->secret, $payload);

    $response->assertOk();
});

test('a webhook with a completely malformed JSON payload does not crash the endpoint', function () {
    $response = sendSignedWebhook($this->secret, 'not valid json {{{');

    $response->assertStatus(200);
});
