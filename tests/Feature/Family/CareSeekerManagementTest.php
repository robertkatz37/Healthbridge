<?php

use App\Models\CareSeeker;
use App\Models\Family;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('family');
    $this->family = Family::create(['user_id' => $this->user->id]);
});

test('family can view their care seekers list', function () {
    $response = $this->actingAs($this->user)->get(route('family.care-seekers.index'));

    $response->assertOk();
});

test('family can create a care seeker profile', function () {
    $response = $this->actingAs($this->user)->post(route('family.care-seekers.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'age' => 78,
    ]);

    $response->assertRedirect();
    expect($this->family->careSeekers()->count())->toBe(1);
    expect($this->family->careSeekers()->first()->full_name)->toBe('Jane Doe');
});

test('creating a care seeker requires first and last name', function () {
    $response = $this->actingAs($this->user)->post(route('family.care-seekers.store'), []);

    $response->assertSessionHasErrors(['first_name', 'last_name']);
});

test('family can update a care seeker full profile', function () {
    $careSeeker = $this->family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $response = $this->actingAs($this->user)->put(route('family.care-seekers.update', $careSeeker), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'care_type_needed' => 'assisted_living',
        'mobility' => 'cane_walker',
        'memory_status' => 'mild',
        'move_in_timeline' => 'within_30_days',
        'budget_min' => 3000,
        'budget_max' => 5000,
        'adl_needs' => ['bathing', 'dressing'],
        'has_ltc_insurance' => '1',
        'is_veteran' => '1',
    ]);

    $response->assertRedirect();
    $careSeeker->refresh();
    expect($careSeeker->care_type_needed->value)->toBe('assisted_living');
    expect($careSeeker->mobility->value)->toBe('cane_walker');
    expect($careSeeker->adl_needs)->toBe(['bathing', 'dressing']);
    expect($careSeeker->has_ltc_insurance)->toBeTrue();
    expect($careSeeker->is_veteran)->toBeTrue();
});

test('budget_max must be greater than or equal to budget_min', function () {
    $careSeeker = $this->family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $response = $this->actingAs($this->user)->put(route('family.care-seekers.update', $careSeeker), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'budget_min' => 5000,
        'budget_max' => 3000,
    ]);

    $response->assertSessionHasErrors('budget_max');
});

test('family can support multiple care seekers', function () {
    $this->family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $this->family->careSeekers()->create(['first_name' => 'John', 'last_name' => 'Doe']);

    $response = $this->actingAs($this->user)->get(route('family.care-seekers.index'));

    $response->assertOk();
    expect($this->family->careSeekers()->count())->toBe(2);
});

test('family can delete a care seeker profile', function () {
    $careSeeker = $this->family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $response = $this->actingAs($this->user)->delete(route('family.care-seekers.destroy', $careSeeker));

    $response->assertRedirect(route('family.care-seekers.index'));
    expect($this->family->careSeekers()->count())->toBe(0);
});

test('family cannot view another familys care seeker', function () {
    $otherFamily = Family::factory()->create();
    $otherCareSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Not', 'last_name' => 'Yours']);

    $response = $this->actingAs($this->user)->get(route('family.care-seekers.edit', $otherCareSeeker));

    $response->assertStatus(403);
});

test('family cannot update another familys care seeker', function () {
    $otherFamily = Family::factory()->create();
    $otherCareSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Not', 'last_name' => 'Yours']);

    $response = $this->actingAs($this->user)->put(route('family.care-seekers.update', $otherCareSeeker), [
        'first_name' => 'Hijacked', 'last_name' => 'Name',
    ]);

    $response->assertStatus(403);
    expect($otherCareSeeker->fresh()->first_name)->toBe('Not');
});

test('family cannot delete another familys care seeker', function () {
    $otherFamily = Family::factory()->create();
    $otherCareSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Not', 'last_name' => 'Yours']);

    $response = $this->actingAs($this->user)->delete(route('family.care-seekers.destroy', $otherCareSeeker));

    $response->assertStatus(403);
    expect($otherCareSeeker->fresh())->not->toBeNull();
});

test('non family role cannot access care seekers routes', function () {
    $agencyOwner = User::factory()->create()->assignRole('agency_owner');

    $response = $this->actingAs($agencyOwner)->get(route('family.care-seekers.index'));

    $response->assertStatus(403);
});

test('super_admin cannot access family care seeker routes directly', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    $response = $this->actingAs($admin)->get(route('family.care-seekers.index'));

    $response->assertStatus(403);
});
