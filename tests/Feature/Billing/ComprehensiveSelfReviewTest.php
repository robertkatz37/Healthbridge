<?php

use App\Enums\InvoiceType;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;

beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, ['agency_category_id' => $category->id, 'name' => 'Self Review Agency']);
    $this->agency->update(['status' => 'published']);
    $this->owner->update(['current_agency_id' => $this->agency->id]);

    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_review_1', 'stripe_status' => 'active']);

    $this->invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan - Monthly', 'amount' => 99]]);
    app(PaymentService::class)->recordSuccess($this->invoice, 99, 'stripe', 'pi_review_1');

    $failedInvoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Retry', 'amount' => 99]]);
    app(PaymentService::class)->recordFailure($failedInvoice, 99, 'stripe', 'Your card was declined.');
});

test('SELF-REVIEW: Agency Billing page renders with real data and contains every required element, with zero server errors', function () {
    $response = $this->actingAs($this->owner)->get(route('agency.billing.index'));

    $response->assertOk(); // the exact assertion that previously caught the 500 error
    $response->assertSee('Current Plan');
    $response->assertSee('Available Plans');
    $response->assertSee('Upgrade', false);
    $response->assertSee('Downgrade');
    $response->assertSee('Cancel Subscription');
    $response->assertSee('Purchase Featured Listing');
    $response->assertSee('name="coupon_code"', false);
    $response->assertSee('Billing History');
    $response->assertSee('Payment History');
    $response->assertSee('Payment Method');
    $response->assertSee('Next Renewal');
    $response->assertSee('Billing Status');
    $response->assertSee('Subscription Status');
    $response->assertSee($this->invoice->invoice_number);
    $response->assertSee('Failed'); // the failed invoice's status badge
});

test('SELF-REVIEW: canceling state shows Resume Subscription and Canceling status', function () {
    $this->agency->subscription->update(['ends_at' => now()->addDays(10)]);

    $response = $this->actingAs($this->owner)->get(route('agency.billing.index'));

    $response->assertOk();
    $response->assertSee('Resume Subscription');
    $response->assertSee('Canceling');
});

test('SELF-REVIEW: Invoice Detail page renders with line items, Download PDF, and Request Refund', function () {
    $response = $this->actingAs($this->owner)->get(route('agency.billing.invoices.show', $this->invoice));

    $response->assertOk();
    $response->assertSee($this->invoice->invoice_number);
    $response->assertSee('Download PDF');
    $response->assertSee('Request Refund');
    $response->assertSee('Subtotal');
});

test('SELF-REVIEW: Super Admin Dashboard shows Revenue, MRR, ARR, Active Subscriptions, Trial Users, Failed Payments, Recent Transactions, Refund Queue', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Total Revenue');
    $response->assertSee('Monthly Recurring Revenue');
    $response->assertSee('Annual Recurring Revenue');
    $response->assertSee('Active Subscriptions');
    $response->assertSee('Trial Users');
    $response->assertSee('Failed Payments');
    $response->assertSee('Recent Transactions');
    $response->assertSee('Refund Queue');
});

test('SELF-REVIEW: Super Admin Reports page shows the full Financial Overview including ARR, Trial Users, Churn, Top-Selling Plans', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    $response = $this->actingAs($admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertSee('Financial Overview');
    $response->assertSee('Annual Recurring Revenue');
    $response->assertSee('Trial Users');
    $response->assertSee('Churn Rate');
    $response->assertSee('Top-Selling Plans');
    $response->assertSee('Featured Listing Revenue');
});

test('SELF-REVIEW: Admin Subscription Plans page lists every plan and links to edit', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    $response = $this->actingAs($admin)->get(route('admin.billing.plans.index'));

    $response->assertOk();
    $response->assertSee('Premium');
    $response->assertSee('Professional');
    $response->assertSee('Edit Plan');
});

test('SELF-REVIEW: Admin can edit a plan and changes persist', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $plan = Plan::where('code', 'premium')->first();

    $editResponse = $this->actingAs($admin)->get(route('admin.billing.plans.edit', $plan));
    $editResponse->assertOk();
    $editResponse->assertSee('Stripe Price IDs');
    $editResponse->assertSee('Feature Limits');

    $updateResponse = $this->actingAs($admin)->put(route('admin.billing.plans.update', $plan), [
        'name' => 'Premium Updated', 'price_monthly' => 109, 'price_yearly' => 1090,
        'trial_days' => 14, 'stripe_price_id_monthly' => 'price_x', 'stripe_price_id_yearly' => 'price_y',
        'is_active' => '1',
    ]);
    $updateResponse->assertRedirect();
    expect($plan->fresh()->name)->toBe('Premium Updated');
    expect((float) $plan->fresh()->price_monthly)->toBe(109.0);
});

test('SELF-REVIEW: Admin Invoice Management lists invoices across agencies with working filters and detail view', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    $indexResponse = $this->actingAs($admin)->get(route('admin.billing.invoices.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee($this->invoice->invoice_number);
    $indexResponse->assertSee($this->agency->name);

    $filteredResponse = $this->actingAs($admin)->get(route('admin.billing.invoices.index', ['type' => 'featured_listing']));
    $filteredResponse->assertOk();
    $filteredResponse->assertDontSee($this->invoice->invoice_number);

    $showResponse = $this->actingAs($admin)->get(route('admin.billing.invoices.show', $this->invoice));
    $showResponse->assertOk();
    $showResponse->assertSee('Subtotal');
});

test('SELF-REVIEW: Admin Stripe Settings page loads and reflects unconfigured state correctly', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    $response = $this->actingAs($admin)->get(route('admin.settings.stripe.edit'));

    $response->assertOk();
    $response->assertSee('Stripe is not configured');
    $response->assertSee('API Keys');
});

test('SELF-REVIEW: Admin can save Stripe settings with secret fields stored encrypted', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    $response = $this->actingAs($admin)->put(route('admin.settings.stripe.update'), [
        'stripe_key' => 'pk_test_abc', 'stripe_secret' => 'sk_test_xyz', 'stripe_webhook_secret' => 'whsec_123',
    ]);

    $response->assertRedirect();
    $setting = \App\Models\Setting::where('key', 'stripe_secret')->first();
    expect($setting->is_encrypted)->toBeTrue();
    expect(app(\App\Services\Settings\SettingsService::class)->get('stripe_secret'))->toBe('sk_test_xyz');
});
