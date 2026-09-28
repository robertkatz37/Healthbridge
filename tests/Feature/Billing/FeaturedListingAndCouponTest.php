<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use Illuminate\Support\Facades\Http;

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
});

test('BROWSER TEST — purchase Featured Listing: starts a real one-time Stripe Checkout, and only activates once the webhook confirms payment', function () {
    Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_featured_1', 'url' => 'https://checkout.stripe.com/pay/cs_featured_1'], 200)]);

    $checkoutResponse = $this->actingAs($this->owner)->post(route('agency.billing.featured-listing.purchase'));

    $checkoutResponse->assertRedirect('https://checkout.stripe.com/pay/cs_featured_1');
    // Must NOT be featured yet — nothing has actually been paid until
    // the webhook confirms it, exactly like a subscription purchase.
    expect($this->agency->fresh()->is_featured)->toBeFalse();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
        && $request['mode'] === 'payment'
        && $request['metadata[purchase_type]'] === 'featured_listing'
        && $request['metadata[agency_id]'] === (string) $this->agency->id);

    $secret = 'whsec_featured_test';
    config(['services.stripe.webhook_secret' => $secret]);
    $payload = json_encode([
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_featured_1', 'payment_intent' => 'pi_featured_1', 'amount_total' => 4900,
            'metadata' => ['agency_id' => (string) $this->agency->id, 'purchase_type' => 'featured_listing'],
        ]],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $webhookResponse = $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}", 'CONTENT_TYPE' => 'application/json'], $payload);
    $webhookResponse->assertOk();

    $agency = $this->agency->fresh();
    expect($agency->is_featured)->toBeTrue();
    expect($agency->featured_until)->not->toBeNull();
    expect($agency->featured_until->isFuture())->toBeTrue();

    $invoice = $this->agency->invoices()->latest()->first();
    expect($invoice->invoice_type->value)->toBe('featured_listing');
    expect((float) $invoice->total_amount)->toBe(49.0);
    expect($invoice->status->value)->toBe('paid');
    expect($invoice->payments()->first()->stripe_payment_intent_id)->toBe('pi_featured_1');
});

test('a redelivered Featured Listing webhook (Stripe retry) does not double-charge or extend featured_until twice', function () {
    Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_featured_dup', 'url' => 'https://checkout.stripe.com/pay/cs_featured_dup'], 200)]);
    $this->actingAs($this->owner)->post(route('agency.billing.featured-listing.purchase'));

    $secret = 'whsec_featured_dup_test';
    config(['services.stripe.webhook_secret' => $secret]);
    $payload = json_encode([
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_featured_dup', 'payment_intent' => 'pi_featured_dup', 'amount_total' => 4900,
            'metadata' => ['agency_id' => (string) $this->agency->id, 'purchase_type' => 'featured_listing'],
        ]],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    $send = fn () => $this->call('POST', '/webhooks/stripe', [], [], [], ['HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}", 'CONTENT_TYPE' => 'application/json'], $payload);

    $send()->assertOk();
    $send()->assertOk(); // redelivery

    expect($this->agency->invoices()->where('invoice_type', 'featured_listing')->count())->toBe(1);
});

test('an agency whose plan already includes Featured Listing cannot start a purchase checkout for it', function () {
    $professional = Plan::where('code', 'professional')->first();
    $this->agency->subscription->update(['plan_id' => $professional->id]);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.featured-listing.purchase'));

    $response->assertSessionHasErrors('plan');
    expect($this->agency->fresh()->is_featured)->toBeFalse();
});

test('an agency that is already featured cannot start a second purchase checkout', function () {
    $this->agency->update(['is_featured' => true, 'featured_until' => now()->addDays(10)]);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.featured-listing.purchase'));

    $response->assertSessionHasErrors('plan');
});

test('BROWSER TEST — apply coupon: a valid coupon reduces the effective checkout, invalid codes are rejected', function () {
    Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_1', 'url' => 'https://checkout.stripe.com/pay/cs_1'], 200)]);

    $plan = Plan::where('code', 'premium')->first();
    $coupon = Coupon::create(['code' => 'SAVE20', 'type' => 'percentage', 'value' => 20, 'applies_to' => 'subscription', 'is_active' => true]);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.checkout'), [
        'plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'coupon_code' => 'SAVE20',
    ]);

    $response->assertRedirect('https://checkout.stripe.com/pay/cs_1');
});

test('an invalid or expired coupon code is rejected at checkout with a clear error', function () {
    $plan = Plan::where('code', 'premium')->first();
    Coupon::create(['code' => 'EXPIRED10', 'type' => 'fixed', 'value' => 10, 'applies_to' => 'all', 'is_active' => true, 'expires_at' => now()->subDay()]);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.checkout'), [
        'plan_id' => $plan->id, 'billing_cycle' => 'monthly', 'coupon_code' => 'EXPIRED10',
    ]);

    $response->assertSessionHasErrors('coupon_code');
});

test('a coupon restricted to a specific plan cannot be applied to a different plan', function () {
    $premium = Plan::where('code', 'premium')->first();
    $professional = Plan::where('code', 'professional')->first();
    Coupon::create(['code' => 'PROONLY', 'type' => 'percentage', 'value' => 15, 'applies_to' => 'subscription', 'plan_id' => $professional->id, 'is_active' => true]);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.checkout'), [
        'plan_id' => $premium->id, 'billing_cycle' => 'monthly', 'coupon_code' => 'PROONLY',
    ]);

    $response->assertSessionHasErrors('coupon_code');
});

test('a coupon that has exhausted its usage limit is no longer redeemable', function () {
    $coupon = Coupon::create(['code' => 'ONEUSE', 'type' => 'fixed', 'value' => 5, 'applies_to' => 'all', 'is_active' => true, 'max_uses' => 1, 'times_used' => 1]);

    expect($coupon->isRedeemable())->toBeFalse();
});

test('percentage discount math is correct and capped at the subtotal', function () {
    $coupon = Coupon::create(['code' => 'HALF', 'type' => 'percentage', 'value' => 50, 'applies_to' => 'all', 'is_active' => true]);
    $fixedOver = Coupon::create(['code' => 'BIGFIXED', 'type' => 'fixed', 'value' => 1000, 'applies_to' => 'all', 'is_active' => true]);

    expect($coupon->discountFor(100))->toBe(50.0);
    expect($fixedOver->discountFor(100))->toBe(100.0);
});
