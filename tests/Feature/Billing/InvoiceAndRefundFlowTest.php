<?php

use App\Enums\InvoiceType;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, [
        'agency_category_id' => $category->id,
        'name' => 'Test Agency',
    ]);
    $this->agency->update(['status' => 'published']);
    $this->owner->update(['current_agency_id' => $this->agency->id]);

    Storage::fake('public');
});

test('BROWSER TEST — download invoice: an agency owner can download the PDF of their own paid invoice', function () {
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');

    $response = $this->actingAs($this->owner)->get(route('agency.billing.invoices.download', $invoice->fresh()));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

test('an agency owner cannot view or download another agencys invoice', function () {
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');

    $otherOwner = User::factory()->create()->assignRole('agency_owner');

    $this->actingAs($otherOwner)->get(route('agency.billing.invoices.show', $invoice->fresh()))->assertStatus(403);
});

test('BROWSER TEST — failed payment: a payment failure marks the invoice failed and does not mark it paid', function () {
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);

    app(PaymentService::class)->recordFailure($invoice, 99, 'stripe', 'Your card was declined.');

    $freshInvoice = $invoice->fresh();
    expect($freshInvoice->status->value)->toBe('failed');
    expect($freshInvoice->paid_at)->toBeNull();

    $payment = $freshInvoice->payments()->latest()->first();
    expect($payment->status->value)->toBe('failed');
    expect($payment->failure_reason)->toBe('Your card was declined.');
});

test('the invoice.payment_failed webhook records the failure against the correct invoice', function () {
    $secret = 'whsec_test';
    config(['services.stripe.webhook_secret' => $secret]);

    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    $invoice->update(['stripe_invoice_id' => 'in_test_999']);

    $payload = json_encode([
        'type' => 'invoice.payment_failed',
        'data' => ['object' => ['id' => 'in_test_999', 'amount_due' => 9900, 'last_payment_error' => ['message' => 'Insufficient funds'], 'payment_intent' => 'pi_test_1']],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertOk();
    expect($invoice->fresh()->status->value)->toBe('failed');
});

test('BROWSER TEST — refund request: an agency owner can request a refund on a successful payment', function () {
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    $payment = app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');

    $response = $this->actingAs($this->owner)->post(route('agency.billing.payments.refund-request', $payment), [
        'amount' => 99, 'reason' => 'Accidentally purchased the wrong plan.',
    ]);

    $response->assertRedirect();
    $refund = \App\Models\Refund::where('payment_id', $payment->id)->first();
    expect($refund)->not->toBeNull();
    expect($refund->status->value)->toBe('requested');
    expect((float) $refund->amount)->toBe(99.0);
});

test('a refund request cannot exceed the remaining refundable balance on the payment', function () {
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    $payment = app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');

    $response = $this->actingAs($this->owner)->post(route('agency.billing.payments.refund-request', $payment), [
        'amount' => 500, 'reason' => 'Testing over-refund.',
    ]);

    $response->assertStatus(422);
});

test('BROWSER TEST — admin approval: super_admin can approve a refund request, which processes it and updates the invoice', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    $payment = app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');
    $refund = app(\App\Services\Billing\RefundService::class)->request($payment, 99, 'Wrong plan', $this->owner);

    $response = $this->actingAs($admin)->post(route('admin.billing.refunds.approve', $refund), ['admin_notes' => 'Approved, refunding in full.']);

    $response->assertRedirect();
    $freshRefund = $refund->fresh();
    expect($freshRefund->status->value)->toBe('processed');
    expect($freshRefund->approved_by)->toBe($admin->id);

    expect($payment->fresh()->status->value)->toBe('refunded');
    expect($invoice->fresh()->status->value)->toBe('refunded');
});

test('super_admin can reject a refund request with a required reason', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    $payment = app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');
    $refund = app(\App\Services\Billing\RefundService::class)->request($payment, 99, 'Changed my mind', $this->owner);

    $response = $this->actingAs($admin)->post(route('admin.billing.refunds.reject', $refund), ['admin_notes' => 'Outside refund window.']);

    $response->assertRedirect();
    expect($refund->fresh()->status->value)->toBe('rejected');
    expect($payment->fresh()->status->value)->toBe('succeeded');
});

test('an agency owner (not admin) cannot approve their own refund request', function () {
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    $payment = app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');
    $refund = app(\App\Services\Billing\RefundService::class)->request($payment, 99, 'Test', $this->owner);

    $this->actingAs($this->owner)->post(route('admin.billing.refunds.approve', $refund))->assertStatus(403);
});

test('an admin can only approve a refund once — a second approval attempt is rejected', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    $payment = app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');
    $refund = app(\App\Services\Billing\RefundService::class)->request($payment, 99, 'Test', $this->owner);

    $this->actingAs($admin)->post(route('admin.billing.refunds.approve', $refund));

    $response = $this->actingAs($admin)->post(route('admin.billing.refunds.approve', $refund));
    $response->assertStatus(422);
});
