<?php

use App\Models\AgencyCategory;
use App\Models\User;
use App\Services\Agency\AgencyAnalyticsService;
use App\Services\Agency\AgencyProvisioningService;

beforeEach(function () {
    $this->withoutVite();
    $owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($owner, [
        'agency_category_id' => $category->id,
        'name' => 'Analytics Test Agency',
    ]);
    $this->analytics = app(AgencyAnalyticsService::class);
});

test('profile completeness is zero for a bare draft agency', function () {
    expect($this->analytics->profileCompleteness($this->agency))->toBe(0);
});

test('profile completeness accumulates weight correctly across multiple true and false checks', function () {
    // Regression test: profileCompleteness previously used boolean values as
    // PHP array keys, which silently collapse (true => 1, false => 0), so
    // every failing check overwrote every other failing check's weight and
    // every passing check overwrote every other passing check's weight.
    // This test exercises a mix of true/false checks to catch that class
    // of bug if it's ever reintroduced.
    $this->agency->services()->create(['name' => 'Test Service']);   // +20
    $this->agency->coverage()->create(['city' => 'Austin', 'state' => 'TX']); // +15
    $this->agency->hours()->create(['day_of_week' => 1]);            // +15
    // description, phone, address, media all left unset (false)

    expect($this->analytics->profileCompleteness($this->agency->fresh()))->toBe(50);
});

test('profile completeness reaches 100 when every section is filled', function () {
    $this->agency->update([
        'description' => 'A description',
        'phone' => '555-1234',
        'address' => '123 Main St',
    ]);
    $this->agency->services()->create(['name' => 'Test Service']);
    $this->agency->coverage()->create(['city' => 'Austin', 'state' => 'TX']);
    $this->agency->hours()->create(['day_of_week' => 1]);
    $this->agency->media()->create(['type' => 'photo', 'path' => 'test.jpg']);

    expect($this->analytics->profileCompleteness($this->agency->fresh()))->toBe(100);
});

test('summary returns zeroed lead and review counts for a new agency', function () {
    $summary = $this->analytics->summary($this->agency);

    expect($summary['total_leads'])->toBe(0);
    expect($summary['pending_leads'])->toBe(0);
    expect($summary['converted_leads'])->toBe(0);
    expect($summary['total_reviews'])->toBe(0);
});
