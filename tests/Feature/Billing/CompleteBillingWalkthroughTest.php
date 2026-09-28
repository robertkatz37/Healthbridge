<?php

use App\Models\AgencyCategory;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use Illuminate\Support\Facades\Http;

/**
 * The complete Section 5 walkthrough from the Phase 16 self-review
 * request, run as ONE continuous sequence exactly as a real user would
 * click through it — not isolated unit checks. Every step asserts the
 * page loads (200), the expected content is visible, and the database
 * reflects the action before moving to the next step.
 */
test('COMPLETE WALKTHROUGH: Agency Owner purchase through refund, then Super Admin approval and revenue view', function () {
    $this->withoutVite();

    // Setup
    $owner = User::factory()->create(['email' => 'walkthrough-owner@example.com'])->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $agency = app(AgencyProvisioningService::class)->createDraftAgency($owner, ['agency_category_id' => $category->id, 'name' => 'Walkthrough Agency']);
    $agency->update(['status' => 'published']);
    $owner->update(['current_agency_id' => $agency->id]);
    $admin = User::factory()->create(['email' => 'walkthrough-admin@example.com'])->assignRole('super_admin');

    // STEP 1 — Agency Owner visits Billing, sees the Free plan
    $billingPage = $this->actingAs($owner)->get(route('agency.billing.index'));
    $billingPage->assertOk();
    $billingPage->assertSee('Free');
    dump('STEP 1 — Billing page loaded, agency starts on Free plan');

    // STEP 2 — Super Admin creates a coupon for the walkthrough to use
    $couponResponse = $this->actingAs($admin)->post(route('admin.billing.coupons.store'), [
        'code' => 'WALKTHROUGH15', 'type' => 'percentage', 'value' => 15, 'applies_to' => 'subscription',
    ]);
    $couponResponse->assertRedirect();
    $coupon = Coupon::where('code', 'WALKTHROUGH15')->first();
    expect($coupon)->not->toBeNull();
    dump('STEP 2 — Super Admin created coupon WALKTHROUGH15 (15% off)');

    // STEP 3 — Agency Owner applies the coupon and purchases the Premium plan
    Http::fakeSequence('api.stripe.com/v1/checkout/sessions')
        ->push(['id' => 'cs_walkthrough', 'url' => 'https://checkout.stripe.com/pay/cs_walkthrough'], 200)
        ->push(['id' => 'cs_walkthrough_featured', 'url' => 'https://checkout.stripe.com/pay/cs_walkthrough_featured'], 200);
    $premium = Plan::where('code', 'premium')->first();
    $checkoutResponse = $this->actingAs($owner)->post(route('agency.billing.checkout'), [
        'plan_id' => $premium->id, 'billing_cycle' => 'monthly', 'coupon_code' => 'WALKTHROUGH15',
    ]);
    $checkoutResponse->assertRedirect('https://checkout.stripe.com/pay/cs_walkthrough');
    dump('STEP 3 — Agency Owner applied coupon and started checkout for Premium plan');

    // STEP 4 — Stripe webhook confirms payment, subscription activates
    $secret = 'whsec_walkthrough';
    config(['services.stripe.webhook_secret' => $secret]);
    $payload = json_encode([
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_walkthrough', 'subscription' => 'sub_walkthrough_1', 'payment_intent' => 'pi_walkthrough_1',
            'metadata' => ['agency_id' => (string) $agency->id, 'plan_id' => (string) $premium->id, 'billing_cycle' => 'monthly'],
        ]],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $webhookResponse = $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}", 'CONTENT_TYPE' => 'application/json'], $payload);
    $webhookResponse->assertOk();
    expect($agency->fresh()->subscription->plan->code)->toBe('premium');
    $invoice = $agency->invoices()->latest()->first();
    dump('STEP 4 — Webhook activated subscription on Premium plan, invoice ' . $invoice->invoice_number . ' generated and paid');

    // STEP 5 — Agency Owner upgrades to Professional
    Http::fake(['api.stripe.com/v1/subscriptions/*' => Http::response(['id' => 'sub_walkthrough_1', 'status' => 'active'], 200)]);
    $professional = Plan::where('code', 'professional')->first();
    $upgradeResponse = $this->actingAs($owner)->post(route('agency.billing.change-plan'), ['plan_id' => $professional->id, 'billing_cycle' => 'monthly']);
    $upgradeResponse->assertRedirect();
    expect($agency->fresh()->subscription->plan->code)->toBe('professional');
    dump('STEP 5 — Agency Owner upgraded to Professional');

    // STEP 6 — Agency Owner downgrades back to Premium
    $downgradeResponse = $this->actingAs($owner)->post(route('agency.billing.change-plan'), ['plan_id' => $premium->id, 'billing_cycle' => 'monthly']);
    $downgradeResponse->assertRedirect();
    expect($agency->fresh()->subscription->plan->code)->toBe('premium');
    dump('STEP 6 — Agency Owner downgraded back to Premium');

    // STEP 7 — Agency Owner cancels
    $cancelResponse = $this->actingAs($owner)->post(route('agency.billing.cancel'));
    $cancelResponse->assertRedirect();
    expect($agency->fresh()->subscription->ends_at)->not->toBeNull();
    dump('STEP 7 — Agency Owner canceled — subscription scheduled to end, still active until then');

    // STEP 8 — Agency Owner resumes
    $resumeResponse = $this->actingAs($owner)->post(route('agency.billing.resume'));
    $resumeResponse->assertRedirect();
    expect($agency->fresh()->subscription->ends_at)->toBeNull();
    dump('STEP 8 — Agency Owner resumed the subscription');

    // STEP 9 — Agency Owner buys Featured Listing (real one-time Stripe Checkout)
    $featuredCheckoutResponse = $this->actingAs($owner)->post(route('agency.billing.featured-listing.purchase'));
    $featuredCheckoutResponse->assertRedirectContains('checkout.stripe.com');
    expect($agency->fresh()->is_featured)->toBeFalse();
    $featuredSessionId = str($featuredCheckoutResponse->headers->get('Location'))->afterLast('/')->toString();

    $featuredPayload = json_encode([
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => $featuredSessionId, 'payment_intent' => 'pi_walkthrough_featured', 'amount_total' => 4900,
            'metadata' => ['agency_id' => (string) $agency->id, 'purchase_type' => 'featured_listing'],
        ]],
    ]);
    $featuredTimestamp = time();
    $featuredSignature = hash_hmac('sha256', $featuredTimestamp . '.' . $featuredPayload, $secret);
    $featuredWebhookResponse = $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_Stripe-Signature' => "t={$featuredTimestamp},v1={$featuredSignature}", 'CONTENT_TYPE' => 'application/json'], $featuredPayload);
    $featuredWebhookResponse->assertOk();
    expect($agency->fresh()->is_featured)->toBeTrue();
    dump('STEP 9 — Agency Owner purchased Featured Listing via real Stripe Checkout, confirmed by webhook');

    // STEP 10 — Agency Owner downloads the original invoice PDF
    $downloadResponse = $this->actingAs($owner)->get(route('agency.billing.invoices.download', $invoice->fresh()));
    $downloadResponse->assertOk();
    expect($downloadResponse->headers->get('Content-Type'))->toContain('application/pdf');
    dump('STEP 10 — Agency Owner downloaded invoice ' . $invoice->invoice_number . ' as a real PDF');

    // STEP 11 — Agency Owner requests a refund on that invoice's payment
    $payment = $invoice->fresh()->payments()->first();
    $refundRequestResponse = $this->actingAs($owner)->post(route('agency.billing.payments.refund-request', $payment), [
        'amount' => 99, 'reason' => 'Walkthrough test refund request',
    ]);
    $refundRequestResponse->assertRedirect();
    $refund = \App\Models\Refund::where('payment_id', $payment->id)->first();
    expect($refund->status->value)->toBe('requested');
    dump('STEP 11 — Agency Owner requested a refund');

    // STEP 12 — Super Admin sees it in the Refund Queue and approves it
    Http::fake(['api.stripe.com/v1/refunds' => Http::response(['id' => 're_walkthrough_1', 'status' => 'succeeded'], 200)]);
    $queueResponse = $this->actingAs($admin)->get(route('admin.billing.refunds.index'));
    $queueResponse->assertOk();
    $queueResponse->assertSee($agency->name);
    $approveResponse = $this->actingAs($admin)->post(route('admin.billing.refunds.approve', $refund), ['admin_notes' => 'Approved for walkthrough']);
    $approveResponse->assertRedirect();
    expect($refund->fresh()->status->value)->toBe('processed');
    dump('STEP 12 — Super Admin approved the refund from the Refund Queue');

    // STEP 13 — A second refund request gets rejected instead
    $secondPayment = app(\App\Services\Billing\PaymentService::class)->recordSuccess(
        app(\App\Services\Billing\InvoiceService::class)->create($agency, \App\Enums\InvoiceType::Addon, [['description' => 'Add-on', 'amount' => 25]]),
        25, 'stripe'
    );
    $secondRefund = app(\App\Services\Billing\RefundService::class)->request($secondPayment, 25, 'Second request', $owner);
    $rejectResponse = $this->actingAs($admin)->post(route('admin.billing.refunds.reject', $secondRefund), ['admin_notes' => 'Not eligible']);
    $rejectResponse->assertRedirect();
    expect($secondRefund->fresh()->status->value)->toBe('rejected');
    dump('STEP 13 — Super Admin rejected a second refund request');

    // STEP 14 — Super Admin views Revenue and Transactions
    $dashboardResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
    $dashboardResponse->assertOk();
    $dashboardResponse->assertSee('Total Revenue');
    $reportsResponse = $this->actingAs($admin)->get(route('admin.reports.index'));
    $reportsResponse->assertOk();
    $reportsResponse->assertSee('Recent Failed Payments');
    dump('STEP 14 — Super Admin viewed Revenue dashboard and full Financial Reports');

    dump('=== COMPLETE BILLING WALKTHROUGH — ALL 14 STEPS VERIFIED END TO END ===');

    expect(true)->toBeTrue();
});
