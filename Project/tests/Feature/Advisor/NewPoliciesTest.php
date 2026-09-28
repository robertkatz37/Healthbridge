<?php

use App\Models\Advisor;
use App\Models\AdvisorTerritory;
use App\Models\Family;
use App\Models\TourRequest;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

// ─── AdvisorTaskPolicy ──────────────────────────────────────────────────────

test('advisor can view update and delete their own task', function () {
    $user = User::factory()->create()->assignRole('advisor');
    $advisor = Advisor::create(['user_id' => $user->id, 'is_active' => true]);
    $task = $advisor->tasks()->create(['title' => 'Test', 'due_at' => now()->addDay(), 'priority' => 'low']);

    expect($user->can('view', $task))->toBeTrue();
    expect($user->can('update', $task))->toBeTrue();
    expect($user->can('delete', $task))->toBeTrue();
});

test('advisor cannot manage a task belonging to an unrelated advisor', function () {
    $user = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $user->id, 'is_active' => true]);
    $otherAdvisor = Advisor::factory()->create();
    $task = $otherAdvisor->tasks()->create(['title' => 'Test', 'due_at' => now()->addDay(), 'priority' => 'low']);

    expect($user->can('view', $task))->toBeFalse();
    expect($user->can('update', $task))->toBeFalse();
    expect($user->can('delete', $task))->toBeFalse();
});

test('super_admin can manage any task', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $task = \App\Models\AdvisorTask::factory()->create();

    expect($admin->can('update', $task))->toBeTrue();
    expect($admin->can('delete', $task))->toBeTrue();
});

// ─── TourRequestPolicy ──────────────────────────────────────────────────────

test('advisor can manage a tour on their own lead', function () {
    $user = User::factory()->create()->assignRole('advisor');
    $advisor = Advisor::create(['user_id' => $user->id, 'is_active' => true]);
    $lead = $advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $tour = $lead->tourRequests()->create([
        'family_id' => $lead->family_id, 'agency_id' => \App\Models\Agency::factory()->create()->id,
        'requested_date' => now()->addDays(3)->toDateString(),
    ]);

    expect($user->can('view', $tour))->toBeTrue();
    expect($user->can('update', $tour))->toBeTrue();
    expect($user->can('delete', $tour))->toBeTrue();
});

test('advisor cannot manage a tour on an unrelated lead', function () {
    $user = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $user->id, 'is_active' => true]);
    $otherAdvisor = Advisor::factory()->create();
    $lead = $otherAdvisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $tour = $lead->tourRequests()->create([
        'family_id' => $lead->family_id, 'agency_id' => \App\Models\Agency::factory()->create()->id,
        'requested_date' => now()->addDays(3)->toDateString(),
    ]);

    expect($user->can('update', $tour))->toBeFalse();
});

// ─── AdvisorTerritoryPolicy ─────────────────────────────────────────────────

test('advisor can update and delete their own territory', function () {
    $user = User::factory()->create()->assignRole('advisor');
    $advisor = Advisor::create(['user_id' => $user->id, 'is_active' => true]);
    $territory = $advisor->territories()->create(['city' => 'Austin', 'state' => 'TX']);

    expect($user->can('update', $territory))->toBeTrue();
    expect($user->can('delete', $territory))->toBeTrue();
});

test('advisor cannot delete another advisors territory via policy', function () {
    $user = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $user->id, 'is_active' => true]);
    $otherAdvisor = Advisor::factory()->create();
    $territory = $otherAdvisor->territories()->create(['city' => 'Austin', 'state' => 'TX']);

    expect($user->can('delete', $territory))->toBeFalse();
});
