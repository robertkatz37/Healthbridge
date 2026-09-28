<?php

use App\Models\AgencyCategory;
use App\Models\Plan;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;
use App\Services\Agency\PlanLimitService;

beforeEach(function () {
    $this->withoutVite();
    $owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($owner, [
        'agency_category_id' => $category->id,
        'name' => 'Test Agency',
    ]);
    $this->planLimits = app(PlanLimitService::class);
});

test('free plan feature values match seeded configuration', function () {
    expect($this->planLimits->limit($this->agency, 'max_staff_accounts'))->toBe(1);
    expect($this->planLimits->limit($this->agency, 'max_leads_per_month'))->toBe(5);
    expect($this->planLimits->hasFeature($this->agency, 'featured_listing'))->toBeFalse();
    expect($this->planLimits->hasFeature($this->agency, 'analytics_dashboard'))->toBeFalse();
});

test('enterprise plan unlimited features resolve to PHP_INT_MAX', function () {
    $enterprise = Plan::where('code', 'enterprise')->first();
    $this->agency->subscription->update(['plan_id' => $enterprise->id]);

    expect($this->planLimits->limit($this->agency->fresh(), 'max_staff_accounts'))->toBe(PHP_INT_MAX);
    expect($this->planLimits->remainingStaffSlots($this->agency->fresh()))->toBe('Unlimited');
});

test('undefined feature codes fail closed to zero/false', function () {
    expect($this->planLimits->limit($this->agency, 'nonexistent_feature'))->toBe(0);
    expect($this->planLimits->hasFeature($this->agency, 'nonexistent_feature'))->toBeFalse();
});

test('current plan name reflects the active subscription', function () {
    expect($this->planLimits->currentPlanName($this->agency))->toBe('Free');

    $premium = Plan::where('code', 'premium')->first();
    $this->agency->subscription->update(['plan_id' => $premium->id]);

    expect($this->planLimits->currentPlanName($this->agency->fresh()))->toBe('Premium');
});
