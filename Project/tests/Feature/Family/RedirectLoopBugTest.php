<?php

use App\Models\Family;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

test('family role user without a Family row does not redirect loop and reaches the dashboard', function () {
    // Regression test for a real bug: a 'family'-role user with no Family
    // row (e.g. role assigned via Admin Panel rather than registration)
    // would infinitely redirect between /dashboard and /family/dashboard.
    // EnsureFamilyRecordExists middleware self-heals by creating the
    // missing row before the controller runs.
    $user = User::factory()->create()->assignRole('family');
    expect(Family::where('user_id', $user->id)->exists())->toBeFalse();

    $response = $this->actingAs($user)->get(route('family.dashboard'));

    $response->assertOk();
    expect(Family::where('user_id', $user->id)->exists())->toBeTrue();
});

test('generic dashboard route correctly resolves to family dashboard without looping for a role-only family user', function () {
    $user = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('family.dashboard'));

    $followUp = $this->actingAs($user)->get(route('family.dashboard'));
    $followUp->assertOk();
});

test('every family sub-route self-heals a missing Family row via the shared middleware', function () {
    $user = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($user)->get(route('family.care-seekers.index'));

    $response->assertOk();
    expect(Family::where('user_id', $user->id)->exists())->toBeTrue();
});
