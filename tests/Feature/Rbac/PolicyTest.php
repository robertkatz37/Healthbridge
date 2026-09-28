<?php

use App\Models\Agency;
use App\Models\CareSeeker;
use App\Models\Family;
use App\Models\Review;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

// ─── UserPolicy ───────────────────────────────────────────────────────────────

test('super_admin can view any user', function () {
    $admin  = User::factory()->create()->assignRole('super_admin');
    $target = User::factory()->create();

    expect($admin->can('view', $target))->toBeTrue();
});

test('family user cannot view other users', function () {
    $family = User::factory()->create()->assignRole('family');
    $target = User::factory()->create();

    expect($family->can('view', $target))->toBeFalse();
});

test('user can always update their own profile', function () {
    $user = User::factory()->create()->assignRole('family');

    expect($user->can('update', $user))->toBeTrue();
});

test('family user cannot update another users profile', function () {
    $family = User::factory()->create()->assignRole('family');
    $other  = User::factory()->create();

    expect($family->can('update', $other))->toBeFalse();
});

test('platform_admin can view and update any user', function () {
    $admin  = User::factory()->create()->assignRole('platform_admin');
    $target = User::factory()->create();

    expect($admin->can('view', $target))->toBeTrue();
    expect($admin->can('update', $target))->toBeTrue();
});

test('nobody can delete themselves via admin policy', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    expect($admin->can('delete', $admin))->toBeFalse();
});

// ─── AgencyPolicy ─────────────────────────────────────────────────────────────

test('agency_owner can update their own agency', function () {
    $owner  = User::factory()->create()->assignRole('agency_owner');
    $agency = Agency::factory()->create(['user_id' => $owner->id]);

    expect($owner->can('update', $agency))->toBeTrue();
});

test('agency_owner cannot update another owners agency', function () {
    $owner1 = User::factory()->create()->assignRole('agency_owner');
    $owner2 = User::factory()->create()->assignRole('agency_owner');
    $agency = Agency::factory()->create(['user_id' => $owner1->id]);

    expect($owner2->can('update', $agency))->toBeFalse();
});

test('moderator can moderate any agency', function () {
    $moderator = User::factory()->create()->assignRole('moderator');
    $agency    = Agency::factory()->create();

    expect($moderator->can('moderate', $agency))->toBeTrue();
});

test('family user cannot moderate an agency', function () {
    $family = User::factory()->create()->assignRole('family');
    $agency = Agency::factory()->create();

    expect($family->can('moderate', $agency))->toBeFalse();
});

// ─── FamilyPolicy ─────────────────────────────────────────────────────────────

test('family user can view their own family record', function () {
    $user   = User::factory()->create()->assignRole('family');
    $family = Family::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $family))->toBeTrue();
});

test('family user cannot view another family record', function () {
    $user1   = User::factory()->create()->assignRole('family');
    $user2   = User::factory()->create()->assignRole('family');
    $family2 = Family::factory()->create(['user_id' => $user2->id]);

    expect($user1->can('view', $family2))->toBeFalse();
});

// ─── ReviewPolicy ─────────────────────────────────────────────────────────────

test('family user can submit a review', function () {
    $user = User::factory()->create()->assignRole('family');

    expect($user->can('create', Review::class))->toBeTrue();
});

test('agency_owner cannot submit a review', function () {
    $owner = User::factory()->create()->assignRole('agency_owner');

    expect($owner->can('create', Review::class))->toBeFalse();
});

test('moderator can moderate reviews', function () {
    $moderator = User::factory()->create()->assignRole('moderator');
    $review    = Review::factory()->create();

    expect($moderator->can('moderate', $review))->toBeTrue();
});

test('super_admin bypasses all policy checks', function () {
    $admin  = User::factory()->create()->assignRole('super_admin');
    $target = User::factory()->create();
    $agency = Agency::factory()->create();

    // super_admin CAN delete other users (but not self — enforced by delete() method)
    expect($admin->can('delete', $target))->toBeTrue();
    expect($admin->can('update', $agency))->toBeTrue();
    expect($admin->can('viewAny', User::class))->toBeTrue();
});

test('super_admin cannot delete their own account via admin policy', function () {
    $admin = User::factory()->create()->assignRole('super_admin');

    // Self-deletion is blocked at the policy delete() method level regardless of role.
    expect($admin->can('delete', $admin))->toBeFalse();
});
