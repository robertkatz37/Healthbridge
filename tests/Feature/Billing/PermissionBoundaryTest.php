<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;

beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, ['agency_category_id' => $category->id, 'name' => 'Boundary Test Agency']);
    $this->agency->update(['status' => 'published']);
    $this->owner->update(['current_agency_id' => $this->agency->id]);
});

test('PERMISSION: Family cannot access any Agency Billing route', function () {
    $family = User::factory()->create()->assignRole('family');

    $this->actingAs($family)->get(route('agency.billing.index'))->assertStatus(403);
    $this->actingAs($family)->post(route('agency.billing.checkout'))->assertStatus(403);
    $this->actingAs($family)->post(route('agency.billing.cancel'))->assertStatus(403);
    $this->actingAs($family)->post(route('agency.billing.featured-listing.purchase'))->assertStatus(403);
});

test('PERMISSION: Advisor cannot access any Agency Billing route', function () {
    $advisor = User::factory()->create()->assignRole('advisor');

    $this->actingAs($advisor)->get(route('agency.billing.index'))->assertStatus(403);
    $this->actingAs($advisor)->post(route('agency.billing.cancel'))->assertStatus(403);
});

test('PERMISSION: Agency Staff (billing.manage_own not granted to this role) cannot access the Billing page or any billing action', function () {
    $staff = User::factory()->create()->assignRole('agency_staff');
    $staff->update(['current_agency_id' => $this->agency->id]);
    DB::table('agency_staff')->insert(['agency_id' => $this->agency->id, 'user_id' => $staff->id, 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($staff)->get(route('agency.billing.index'))->assertStatus(403);
    $this->actingAs($staff)->post(route('agency.billing.checkout'))->assertStatus(403);
    $this->actingAs($staff)->post(route('agency.billing.cancel'))->assertStatus(403);
    $this->actingAs($staff)->post(route('agency.billing.downgrade-to-free'))->assertStatus(403);
    $this->actingAs($staff)->post(route('agency.billing.featured-listing.purchase'))->assertStatus(403);
});

test('PERMISSION: Agency Staff cannot access platform-wide finance (admin billing pages)', function () {
    $staff = User::factory()->create()->assignRole('agency_staff');

    $this->actingAs($staff)->get(route('admin.dashboard'))->assertStatus(403);
    $this->actingAs($staff)->get(route('admin.reports.index'))->assertStatus(403);
    $this->actingAs($staff)->get(route('admin.billing.coupons.index'))->assertStatus(403);
    $this->actingAs($staff)->get(route('admin.billing.refunds.index'))->assertStatus(403);
    $this->actingAs($staff)->get(route('admin.billing.plans.index'))->assertStatus(403);
    $this->actingAs($staff)->get(route('admin.billing.invoices.index'))->assertStatus(403);
});

test('PERMISSION: Agency Owner CAN manage their own billing', function () {
    $response = $this->actingAs($this->owner)->get(route('agency.billing.index'));

    $response->assertOk();
});

test('PERMISSION: Agency Owner CANNOT manage a different agencys billing', function () {
    $otherOwner = User::factory()->create()->assignRole('agency_owner');
    $otherCategory = AgencyCategory::first();
    $otherAgency = app(AgencyProvisioningService::class)->createDraftAgency($otherOwner, ['agency_category_id' => $otherCategory->id, 'name' => 'Other Agency']);
    $otherAgency->update(['status' => 'published']);

    // The owner is scoped to THEIR OWN currentAgency() regardless of
    // which agency ID appears anywhere in the request — there's no
    // agency_id parameter on these routes to tamper with, so the only
    // way to test cross-agency access is confirming currentAgency()
    // resolution itself is correct (already covered elsewhere) and
    // that switching current_agency_id to an agency they don't own
    // is not itself possible via this billing surface.
    $invoice = app(\App\Services\Billing\InvoiceService::class)->create($otherAgency, \App\Enums\InvoiceType::Subscription, [['description' => 'x', 'amount' => 10]]);

    $this->actingAs($this->owner)->get(route('agency.billing.invoices.show', $invoice))->assertStatus(403);
});

test('PERMISSION: Super Admin can manage every subscription and payment across every agency', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $invoice = app(\App\Services\Billing\InvoiceService::class)->create($this->agency, \App\Enums\InvoiceType::Subscription, [['description' => 'x', 'amount' => 99]]);
    $payment = app(\App\Services\Billing\PaymentService::class)->recordSuccess($invoice, 99, 'stripe');
    $refund = app(\App\Services\Billing\RefundService::class)->request($payment, 99, 'Test', $this->owner);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.billing.coupons.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.billing.refunds.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.billing.plans.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.billing.invoices.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.billing.invoices.show', $invoice))->assertOk();
    $this->actingAs($admin)->post(route('admin.billing.refunds.approve', $refund))->assertRedirect();
});

test('PERMISSION: a family user cannot access the admin refund approval action even via direct POST', function () {
    $family = User::factory()->create()->assignRole('family');
    $invoice = app(\App\Services\Billing\InvoiceService::class)->create($this->agency, \App\Enums\InvoiceType::Subscription, [['description' => 'x', 'amount' => 99]]);
    $payment = app(\App\Services\Billing\PaymentService::class)->recordSuccess($invoice, 99, 'stripe');
    $refund = app(\App\Services\Billing\RefundService::class)->request($payment, 99, 'Test', $this->owner);

    $this->actingAs($family)->post(route('admin.billing.refunds.approve', $refund))->assertStatus(403);
});
