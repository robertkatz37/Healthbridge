<?php

use App\Models\AgencyCategory;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create();
    $this->owner->assignRole('agency_owner');
    $this->category = AgencyCategory::first() ?? AgencyCategory::factory()->create();
});

test('agency owner can view step 1 of the wizard', function () {
    $response = $this->actingAs($this->owner)->get(route('agency.register.step1'));

    $response->assertOk();
    $response->assertSee('Tell us about your agency');
});

test('non agency_owner roles cannot access the onboarding wizard', function () {
    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->get(route('agency.register.step1'));

    $response->assertStatus(403);
});

test('step 1 creates a draft agency with an auto-provisioned subscription', function () {
    $response = $this->actingAs($this->owner)->post(route('agency.register.step1.store'), [
        'name' => 'Sunrise Manor',
        'agency_category_id' => $this->category->id,
        'description' => 'A great place.',
    ]);

    $response->assertRedirect(route('agency.register.step2'));

    $this->owner->refresh();
    $agency = $this->owner->agency;

    expect($agency)->not->toBeNull();
    expect($agency->name)->toBe('Sunrise Manor');
    expect($agency->status->value)->toBe('draft');
    expect($agency->onboarding_step)->toBe(2);
    expect($agency->subscription)->not->toBeNull();
    expect($agency->subscription->plan->code)->toBe('free');
    expect($agency->subscription->stripe_id)->toStartWith('local_');
});

test('step 1 requires a name and category', function () {
    $response = $this->actingAs($this->owner)->post(route('agency.register.step1.store'), []);

    $response->assertSessionHasErrors(['name', 'agency_category_id']);
});

test('step 2 saves multiple services', function () {
    $this->actingAs($this->owner)->post(route('agency.register.step1.store'), [
        'name' => 'Sunrise Manor',
        'agency_category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->owner)->post(route('agency.register.step2.store'), [
        'services' => [
            ['name' => 'Personal Care', 'price_from' => 1200],
            ['name' => 'Medication Management', 'price_from' => 800],
        ],
    ]);

    $response->assertRedirect(route('agency.register.step3'));

    $agency = $this->owner->fresh()->agency;
    expect($agency->services()->count())->toBe(2);
    expect($agency->onboarding_step)->toBe(3);
});

test('step 2 requires at least one service', function () {
    $this->actingAs($this->owner)->post(route('agency.register.step1.store'), [
        'name' => 'Sunrise Manor',
        'agency_category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->owner)->post(route('agency.register.step2.store'), [
        'services' => [],
    ]);

    $response->assertSessionHasErrors('services');
});

test('step 3 saves coverage areas and business hours', function () {
    $this->actingAs($this->owner)->post(route('agency.register.step1.store'), [
        'name' => 'Sunrise Manor',
        'agency_category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->owner)->post(route('agency.register.step3.store'), [
        'coverage' => [
            ['city' => 'Austin', 'state' => 'TX', 'radius_miles' => 25],
        ],
        'days' => [
            0 => ['is_closed' => '1'],
            1 => ['open_time' => '09:00', 'close_time' => '17:00'],
        ],
    ]);

    $response->assertRedirect(route('agency.register.step4'));

    $agency = $this->owner->fresh()->agency;
    expect($agency->coverage()->count())->toBe(1);
    expect($agency->hours()->count())->toBeGreaterThanOrEqual(1);
});

test('resuming onboarding redirects to the correct in-progress step', function () {
    $this->actingAs($this->owner)->post(route('agency.register.step1.store'), [
        'name' => 'Sunrise Manor',
        'agency_category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->owner)->get(route('agency.register.start'));

    $response->assertRedirect(route('agency.register.step2'));
});

test('completed onboarding cannot be submitted without services and coverage', function () {
    $this->actingAs($this->owner)->post(route('agency.register.step1.store'), [
        'name' => 'Sunrise Manor',
        'agency_category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->owner)->post(route('agency.register.complete'));

    $response->assertSessionHasErrors('submission');

    $agency = $this->owner->fresh()->agency;
    expect($agency->status->value)->toBe('draft');
});

test('completing onboarding with all requirements submits agency for review', function () {
    $this->actingAs($this->owner)->post(route('agency.register.step1.store'), [
        'name' => 'Sunrise Manor',
        'agency_category_id' => $this->category->id,
    ]);
    $this->actingAs($this->owner)->post(route('agency.register.step2.store'), [
        'services' => [['name' => 'Personal Care']],
    ]);
    $this->actingAs($this->owner)->post(route('agency.register.step3.store'), [
        'coverage' => [['city' => 'Austin', 'state' => 'TX']],
    ]);

    $response = $this->actingAs($this->owner)->post(route('agency.register.complete'));

    $response->assertRedirect(route('agency.dashboard'));

    $agency = $this->owner->fresh()->agency;
    expect($agency->status->value)->toBe('pending_review');
    expect($agency->onboarding_completed_at)->not->toBeNull();
});

test('onboarding submission is logged in activity log', function () {
    $this->actingAs($this->owner)->post(route('agency.register.step1.store'), [
        'name' => 'Sunrise Manor',
        'agency_category_id' => $this->category->id,
    ]);
    $this->actingAs($this->owner)->post(route('agency.register.step2.store'), [
        'services' => [['name' => 'Personal Care']],
    ]);
    $this->actingAs($this->owner)->post(route('agency.register.step3.store'), [
        'coverage' => [['city' => 'Austin', 'state' => 'TX']],
    ]);
    $this->actingAs($this->owner)->post(route('agency.register.complete'));

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $this->owner->id,
        'description' => 'Agency submitted for review',
    ]);
});
