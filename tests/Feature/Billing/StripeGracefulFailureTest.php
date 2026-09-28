<?php

use App\Models\AgencyCategory;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\RefundService;
use Illuminate\Support\Facades\Http;

/**
 * Reproduces the exact production error report: no STRIPE_SECRET
 * configured, so every outbound Stripe call gets Stripe's own
 * "You did not provide an API key" 401 response. Confirms this never
 * again reaches the user as an unhandled 500 with a raw stack trace —
 * every Stripe-calling action must degrade to a friendly redirect.
 */
beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, ['agency_category_id' => $category->id, 'name' => 'No Stripe Key Agency']);
    $this->agency->update(['status' => 'published']);
    $this->owner->update(['current_agency_id' => $this->agency->id]);

    // Simulates the exact real-world condition: Stripe respects the
    // request but rejects it for lacking an API key.
    Http::fake([
        'api.stripe.com/*' => Http::response(
            ['error' => ['message' => 'You did not provide an API key. You need to provide your API key in the Authorization header, using Bearer auth (e.g. \'Authorization: Bearer YOUR_SECRET_KEY\').', 'type' => 'invalid_request_error']],
            401
        ),
    ]);
});

test('checkout with no Stripe API key configured redirects with a friendly message instead of a 500', function () {
    $plan = Plan::where('code', 'premium')->first();

    $response = $this->actingAs($this->owner)->post(route('agency.billing.checkout'), [
        'plan_id' => $plan->id, 'billing_cycle' => 'monthly',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('stripe');
    $response->assertSessionDoesntHaveErrors(['plan_id', 'billing_cycle']);
});

test('the friendly message specifically calls out a configuration problem for a 401, not a generic payment failure', function () {
    $plan = Plan::where('code', 'premium')->first();

    $response = $this->actingAs($this->owner)->post(route('agency.billing.checkout'), [
        'plan_id' => $plan->id, 'billing_cycle' => 'monthly',
    ]);

    $errors = session('errors');
    expect($errors->first('stripe'))->toContain('not yet fully configured');
});

test('canceling a real (non-local) subscription with no Stripe key configured degrades gracefully', function () {
    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_but_no_key', 'stripe_status' => 'active']);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.cancel'));

    $response->assertRedirect();
    $response->assertSessionHasErrors('stripe');
    // The subscription must NOT have been partially mutated by a failed call.
    expect($this->agency->fresh()->subscription->ends_at)->toBeNull();
});

test('changing plans with no Stripe key configured degrades gracefully and does not silently change the plan', function () {
    $premium = Plan::where('code', 'premium')->first();
    $professional = Plan::where('code', 'professional')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_but_no_key', 'stripe_status' => 'active']);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.change-plan'), [
        'plan_id' => $professional->id, 'billing_cycle' => 'monthly',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('stripe');
    expect($this->agency->fresh()->subscription->plan_id)->toBe($premium->id);
});

test('resuming with no Stripe key configured degrades gracefully', function () {
    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_but_no_key', 'stripe_status' => 'active', 'ends_at' => now()->addDays(5)]);

    $response = $this->actingAs($this->owner)->post(route('agency.billing.resume'));

    $response->assertRedirect();
    $response->assertSessionHasErrors('stripe');
    expect($this->agency->fresh()->subscription->ends_at)->not->toBeNull();
});

test('approving a refund with no Stripe key configured degrades gracefully and does not mark it processed', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $invoice = app(InvoiceService::class)->create($this->agency, \App\Enums\InvoiceType::Subscription, [['description' => 'x', 'amount' => 99]]);
    $payment = app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe', 'pi_real_but_no_key');
    $refund = app(RefundService::class)->request($payment, 99, 'Test', $this->owner);

    $response = $this->actingAs($admin)->post(route('admin.billing.refunds.approve', $refund));

    $response->assertRedirect();
    $response->assertSessionHasErrors('stripe');
    expect($refund->fresh()->status->value)->toBe('requested');
});

test('a local-placeholder subscription (never completed real checkout) never calls Stripe, so cancel/resume/downgrade succeed even with no key configured', function () {
    // The agency's subscription is still the Free-plan local placeholder
    // from provisioning — no real Stripe subscription exists yet, so
    // these actions should succeed locally without ever calling Stripe.
    $response = $this->actingAs($this->owner)->post(route('agency.billing.downgrade-to-free'));

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors('stripe');
    expect($this->agency->fresh()->subscription->plan->code)->toBe('free');
});

test('the friendly Stripe error message actually renders visibly on the billing page, not just in session', function () {
    $plan = Plan::where('code', 'premium')->first();

    $this->actingAs($this->owner)->post(route('agency.billing.checkout'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly']);
    $response = $this->actingAs($this->owner)->get(route('agency.billing.index'));

    $response->assertOk();
    $response->assertSee('not yet fully configured');
});

test('the friendly Stripe error message renders visibly on the admin refund queue page', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $invoice = app(InvoiceService::class)->create($this->agency, \App\Enums\InvoiceType::Subscription, [['description' => 'x', 'amount' => 99]]);
    $payment = app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe', 'pi_real_but_no_key');
    $refund = app(RefundService::class)->request($payment, 99, 'Test', $this->owner);

    $this->actingAs($admin)->post(route('admin.billing.refunds.approve', $refund));
    $response = $this->actingAs($admin)->get(route('admin.billing.refunds.index'));

    $response->assertOk();
    $response->assertSee('not yet fully configured');
});
