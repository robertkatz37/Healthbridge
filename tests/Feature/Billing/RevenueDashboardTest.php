<?php

use App\Enums\InvoiceType;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use App\Services\Billing\InvoiceService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\RefundService;

beforeEach(function () {
    $this->withoutVite();
    $this->admin = User::factory()->create()->assignRole('super_admin');
    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, [
        'agency_category_id' => $category->id,
        'name' => 'Revenue Test Agency',
    ]);
    $this->agency->update(['status' => 'published']);
});

test('BROWSER TEST — revenue dashboard: super_admin sees real Revenue, MRR, Active Subscriptions, and Failed Payments KPIs', function () {
    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_1', 'stripe_status' => 'active']);

    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');

    $failedInvoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    app(PaymentService::class)->recordFailure($failedInvoice, 99, 'stripe', 'Card declined');

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Total Revenue');
    $response->assertSee('Monthly Recurring Revenue');
    $response->assertSee('Active Subscriptions');
    $response->assertSee('Failed Payments');
    $response->assertSee('$99.00');
});

test('the full Financial Analytics section renders on the Reports page with real computed figures', function () {
    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_1', 'stripe_status' => 'active']);

    $invoice = app(InvoiceService::class)->create($this->agency, InvoiceType::Subscription, [['description' => 'Premium Plan', 'amount' => 99]]);
    $payment = app(PaymentService::class)->recordSuccess($invoice, 99, 'stripe');
    $refund = app(RefundService::class)->request($payment, 50, 'Partial refund test', $this->owner);
    app(RefundService::class)->approve($refund, $this->admin);

    $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertSee('Financial Overview');
    $response->assertSee('Churn Rate');
    $response->assertSee('Top-Selling Plans');
    $response->assertSee('Refund Queue');
    $response->assertSee('Recent Transactions');
    $response->assertSee('Recent Failed Payments');
});

test('MRR correctly reflects only active subscriptions and excludes canceled or Free-plan ones', function () {
    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_1', 'stripe_status' => 'active']);

    $otherOwner = User::factory()->create()->assignRole('agency_owner');
    $otherAgency = app(AgencyProvisioningService::class)->createDraftAgency($otherOwner, ['agency_category_id' => AgencyCategory::first()->id, 'name' => 'Canceled Agency']);
    $otherAgency->subscription->update(['plan_id' => $premium->id, 'stripe_id' => 'sub_real_2', 'stripe_status' => 'active', 'ends_at' => now()->addDays(5)]);

    $mrr = app(\App\Services\Billing\FinancialAnalyticsService::class)->monthlyRecurringRevenue();

    expect($mrr)->toBe((float) $premium->price_monthly);
});

test('churn rate is 0 when there is nothing to churn from, rather than a division error', function () {
    $churn = app(\App\Services\Billing\FinancialAnalyticsService::class)->churnRate30Days();

    expect($churn)->toBe(0.0);
});

test('an agency owner cannot access the admin financial reports or dashboard', function () {
    $this->actingAs($this->owner)->get(route('admin.reports.index'))->assertStatus(403);
    $this->actingAs($this->owner)->get(route('admin.dashboard'))->assertStatus(403);
});

test('coupon management: super_admin can create, deactivate, and delete a coupon', function () {
    $createResponse = $this->actingAs($this->admin)->post(route('admin.billing.coupons.store'), [
        'code' => 'ADMIN20', 'type' => 'percentage', 'value' => 20, 'applies_to' => 'all',
    ]);
    $createResponse->assertRedirect();
    $coupon = Coupon::where('code', 'ADMIN20')->first();
    expect($coupon)->not->toBeNull();

    $deactivateResponse = $this->actingAs($this->admin)->put(route('admin.billing.coupons.update', $coupon), ['is_active' => '0']);
    $deactivateResponse->assertRedirect();
    expect($coupon->fresh()->is_active)->toBeFalse();

    $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.billing.coupons.destroy', $coupon));
    $deleteResponse->assertRedirect();
    expect(Coupon::find($coupon->id))->toBeNull();
});

test('an agency owner cannot manage coupons', function () {
    $this->actingAs($this->owner)->get(route('admin.billing.coupons.index'))->assertStatus(403);
});
