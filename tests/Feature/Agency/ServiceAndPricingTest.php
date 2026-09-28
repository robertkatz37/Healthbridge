<?php

use App\Models\AgencyCategory;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;

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

test('agency owner can add a service', function () {
    $response = $this->actingAs($this->owner)->post(route('agency.services.store'), [
        'name' => 'Personal Care',
        'price_from' => 1500,
    ]);

    $response->assertRedirect();
    expect($this->agency->services()->count())->toBe(1);
});

test('agency owner can delete their own service', function () {
    $service = $this->agency->services()->create(['name' => 'Test Service']);

    $response = $this->actingAs($this->owner)->delete(route('agency.services.destroy', $service));

    $response->assertRedirect();
    expect($this->agency->services()->count())->toBe(0);
});

test('agency owner cannot delete another agencys service', function () {
    $otherOwner = User::factory()->create()->assignRole('agency_owner');
    $otherAgency = \App\Models\Agency::factory()->create(['user_id' => $otherOwner->id]);
    $otherService = $otherAgency->services()->create(['name' => 'Not Yours']);

    $response = $this->actingAs($this->owner)->delete(route('agency.services.destroy', $otherService));

    $response->assertStatus(403);
    expect($otherAgency->services()->count())->toBe(1);
});

test('agency owner can add a pricing tier and it syncs agency cost range', function () {
    $response = $this->actingAs($this->owner)->post(route('agency.pricing.store'), [
        'room_type' => 'Studio',
        'monthly_price' => 3500,
    ]);

    $response->assertRedirect();
    $this->agency->refresh();
    expect($this->agency->pricing()->count())->toBe(1);
    expect((float) $this->agency->min_monthly_cost)->toBe(3500.0);
    expect((float) $this->agency->max_monthly_cost)->toBe(3500.0);
});

test('agency cost range expands with multiple pricing tiers', function () {
    $this->actingAs($this->owner)->post(route('agency.pricing.store'), ['room_type' => 'Studio', 'monthly_price' => 3000]);
    $this->actingAs($this->owner)->post(route('agency.pricing.store'), ['room_type' => 'Suite', 'monthly_price' => 5500]);

    $this->agency->refresh();
    expect((float) $this->agency->min_monthly_cost)->toBe(3000.0);
    expect((float) $this->agency->max_monthly_cost)->toBe(5500.0);
});

test('agency owner can delete a pricing tier', function () {
    $tier = $this->agency->pricing()->create(['room_type' => 'Studio', 'monthly_price' => 3000]);

    $response = $this->actingAs($this->owner)->delete(route('agency.pricing.destroy', $tier));

    $response->assertRedirect();
    expect($this->agency->pricing()->count())->toBe(0);
});
