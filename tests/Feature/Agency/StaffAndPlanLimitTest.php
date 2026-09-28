<?php

use App\Models\AgencyCategory;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use App\Services\Agency\PlanLimitService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create();
    $this->owner->assignRole('agency_owner');
    $category = AgencyCategory::first();

    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, [
        'agency_category_id' => $category->id,
        'name' => 'Test Agency',
    ]);
    $this->agency->update(['status' => 'published', 'onboarding_completed_at' => now()]);
});

test('free plan allows exactly one staff account', function () {
    $planLimits = app(PlanLimitService::class);

    expect($planLimits->canAddStaff($this->agency))->toBeTrue();
    expect($planLimits->remainingStaffSlots($this->agency))->toBe(1);
});

test('agency owner can add a staff member within plan limits', function () {
    Notification::fake();

    $response = $this->actingAs($this->owner)->post(route('agency.staff.store'), [
        'name' => 'Jane Coordinator',
        'email' => 'jane@testagency.com',
        'job_title' => 'Care Coordinator',
    ]);

    $response->assertRedirect();
    expect($this->agency->staff()->count())->toBe(1);

    $staffUser = \App\Models\User::where('email', 'jane@testagency.com')->first();
    expect($staffUser)->not->toBeNull();
    expect($staffUser->hasRole('agency_staff'))->toBeTrue();
});

test('adding staff beyond the free plan limit is blocked', function () {
    Notification::fake();

    // Consume the only Free-plan staff slot
    $this->actingAs($this->owner)->post(route('agency.staff.store'), [
        'name' => 'First Staff',
        'email' => 'first@testagency.com',
    ]);

    $response = $this->actingAs($this->owner)->post(route('agency.staff.store'), [
        'name' => 'Second Staff',
        'email' => 'second@testagency.com',
    ]);

    $response->assertSessionHasErrors('plan');
    expect($this->agency->staff()->count())->toBe(1);
});

test('professional plan allows up to ten staff accounts', function () {
    $proPlan = Plan::where('code', 'professional')->first();
    $this->agency->subscription->update(['plan_id' => $proPlan->id]);

    $planLimits = app(PlanLimitService::class);
    expect($planLimits->limit($this->agency->fresh(), 'max_staff_accounts'))->toBe(10);
});

test('agency owner can remove a staff member', function () {
    Notification::fake();
    $this->actingAs($this->owner)->post(route('agency.staff.store'), [
        'name' => 'Jane Coordinator', 'email' => 'jane@testagency.com',
    ]);
    $staff = $this->agency->staff()->first();

    $response = $this->actingAs($this->owner)->delete(route('agency.staff.destroy', $staff));

    $response->assertRedirect();
    expect($this->agency->staff()->count())->toBe(0);
});

test('featured listing is blocked on free plan', function () {
    $response = $this->actingAs($this->owner)->post(route('agency.settings.featured'));

    $response->assertSessionHasErrors('plan');
    expect($this->agency->fresh()->is_featured)->toBeFalse();
});

test('featured listing can be enabled on professional plan', function () {
    $proPlan = Plan::where('code', 'professional')->first();
    $this->agency->subscription->update(['plan_id' => $proPlan->id]);

    $response = $this->actingAs($this->owner)->post(route('agency.settings.featured'));

    $response->assertRedirect();
    expect($this->agency->fresh()->is_featured)->toBeTrue();
});
