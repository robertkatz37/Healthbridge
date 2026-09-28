<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
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

test('BROWSER TEST — purchase subscription: agency owner starts checkout for a paid plan and is redirected to Stripe', function () {
    Http::fake([
        'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/pay/cs_test_123'], 200),
    ]);

    $plan = Plan::where('code', 'premium')->first();

    $response = $this->actingAs($this->owner)->post(route('agency.billing.checkout'), [
        'plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
    ]);

    $response->assertRedirect('https://checkout.stripe.com/pay/cs_test_123');
    Http::assertSent(fn ($request) => $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
        && $request['line_items[0][price]'] === $plan->stripe_price_id_monthly
        && $request['metadata[agency_id]'] === (string) $this->agency->id);
});

test('the checkout.session.completed webhook activates the subscription on the correct plan', function () {
    $secret = 'whsec_test';
    config(['services.stripe.webhook_secret' => $secret]);
    $plan = Plan::where('code', 'premium')->first();

    $payload = json_encode([
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_test_123',
            'subscription' => 'sub_test_456',
            'payment_intent' => 'pi_test_789',
            'metadata' => ['agency_id' => (string) $this->agency->id, 'plan_id' => (string) $plan->id, 'billing_cycle' => 'monthly'],
        ]],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_Stripe-Signature' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertOk();

    $subscription = $this->agency->fresh()->subscription;
    expect($subscription->plan_id)->toBe($plan->id);
    expect($subscription->stripe_id)->toBe('sub_test_456');
    expect($subscription->stripe_status)->toBe('active');

    $invoice = $this->agency->invoices()->latest()->first();
    expect($invoice)->not->toBeNull();
    expect($invoice->status->value)->toBe('paid');
    expect((float) $invoice->total_amount)->toBe((float) $plan->price_monthly);
});

test('a webhook with an invalid signature is rejected and does not activate anything', function () {
    config(['services.stripe.webhook_secret' => 'whsec_real']);
    $plan = Plan::where('code', 'premium')->first();

    $payload = json_encode(['type' => 'checkout.session.completed', 'data' => ['object' => ['metadata' => ['agency_id' => (string) $this->agency->id, 'plan_id' => (string) $plan->id]]]]);

    $response = $this->call('POST', '/webhooks/stripe', [], [], [], [
        'HTTP_Stripe-Signature' => 't=' . time() . ',v1=bogus_signature',
        'CONTENT_TYPE' => 'application/json',
    ], $payload);

    $response->assertStatus(400);
    expect($this->agency->fresh()->subscription->stripe_id)->toStartWith('local_');
});

test('BROWSER TEST — upgrade plan: agency owner on an active paid subscription can switch to a higher plan', function () {
    Http::fake(['api.stripe.com/v1/subscriptions/*' => Http::response(['id' => 'sub_real_1', 'status' => 'active'], 200)]);

    $premium = Plan::where('code', 'premium')->first();
    $professional = Plan::where('code', 'professional')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_1', 'stripe_status' => 'active']);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.change-plan'), [
        'plan_id' => $professional->id,
        'billing_cycle' => 'monthly',
    ]);

    $response->assertRedirect();
    expect($this->agency->fresh()->subscription->plan_id)->toBe($professional->id);
});

test('BROWSER TEST — downgrade plan: agency owner can switch to a lower-tier paid plan', function () {
    Http::fake(['api.stripe.com/v1/subscriptions/*' => Http::response(['id' => 'sub_real_1', 'status' => 'active'], 200)]);

    $professional = Plan::where('code', 'professional')->first();
    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $professional->id, 'stripe_id' => 'sub_real_1', 'stripe_status' => 'active']);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.change-plan'), [
        'plan_id' => $premium->id,
        'billing_cycle' => 'monthly',
    ]);

    $response->assertRedirect();
    expect($this->agency->fresh()->subscription->plan_id)->toBe($premium->id);
});

test('changing plans before ever completing checkout (still on the local placeholder) is blocked with a clear message', function () {
    $professional = Plan::where('code', 'professional')->first();

    $response = $this->actingAs($this->owner)->post(route('agency.billing.change-plan'), [
        'plan_id' => $professional->id,
        'billing_cycle' => 'monthly',
    ]);

    $response->assertSessionHasErrors('plan');
});

test('BROWSER TEST — cancel subscription: agency owner cancels, subscription is scheduled to end but stays active until then', function () {
    Http::fake(['api.stripe.com/v1/subscriptions/*' => Http::response(['id' => 'sub_real_1', 'status' => 'active'], 200)]);

    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_1', 'stripe_status' => 'active']);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.cancel'));

    $response->assertRedirect();
    $subscription = $this->agency->fresh()->subscription;
    expect($subscription->ends_at)->not->toBeNull();
    expect($subscription->plan_id)->toBe($premium->id);
});

test('BROWSER TEST — renew subscription (resume): agency owner resumes a subscription scheduled to cancel', function () {
    Http::fake(['api.stripe.com/v1/subscriptions/*' => Http::response(['id' => 'sub_real_1', 'status' => 'active'], 200)]);

    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_1', 'stripe_status' => 'active', 'ends_at' => now()->addDays(10)]);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.resume'));

    $response->assertRedirect();
    expect($this->agency->fresh()->subscription->ends_at)->toBeNull();
});

test('resuming a subscription that is not scheduled to cancel fails validation', function () {
    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_1', 'stripe_status' => 'active', 'ends_at' => null]);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.resume'));

    $response->assertStatus(422);
});

test('an agency owner can explicitly downgrade straight to the Free plan', function () {
    Http::fake(['api.stripe.com/v1/subscriptions/*' => Http::response(['id' => 'sub_real_1', 'status' => 'canceled'], 200)]);

    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_1', 'stripe_status' => 'active']);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.downgrade-to-free'));

    $response->assertRedirect();
    $subscription = $this->agency->fresh()->subscription;
    expect($subscription->plan->code)->toBe('free');
    expect($subscription->stripe_id)->toStartWith('local_');
});

test('a family user cannot access any agency billing route', function () {
    $familyUser = User::factory()->create()->assignRole('family');

    $this->actingAs($familyUser)->get(route('agency.billing.index'))->assertStatus(403);
    $this->actingAs($familyUser)->post(route('agency.billing.cancel'))->assertStatus(403);
});
