<?php

use App\Models\Advisor;
use App\Models\Family;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

test('an advisor with no lead assignment cannot view an unrelated care seeker', function () {
    $advisorUser = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $advisorUser->id, 'is_active' => true]);

    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Unrelated', 'last_name' => 'Seeker']);

    expect($advisorUser->can('view', $careSeeker))->toBeFalse();
});

test('an advisor assigned to a lead for this care seeker can view it', function () {
    $advisorUser = User::factory()->create()->assignRole('advisor');
    $advisor = Advisor::create(['user_id' => $advisorUser->id, 'is_active' => true]);

    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Assigned', 'last_name' => 'Seeker']);
    $family->leads()->create(['care_seeker_id' => $careSeeker->id, 'advisor_id' => $advisor->id, 'status' => 'assigned', 'source' => 'manual']);

    expect($advisorUser->can('view', $careSeeker))->toBeTrue();
});

test('an advisor manager can view a care seeker assigned to their teams lead', function () {
    $managerUser = User::factory()->create()->assignRole('advisor_manager');
    $manager = Advisor::create(['user_id' => $managerUser->id, 'is_active' => true]);
    $memberUser = User::factory()->create()->assignRole('advisor');
    $member = Advisor::create(['user_id' => $memberUser->id, 'is_active' => true, 'advisor_manager_id' => $manager->id]);

    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Team', 'last_name' => 'Seeker']);
    $family->leads()->create(['care_seeker_id' => $careSeeker->id, 'advisor_id' => $member->id, 'status' => 'assigned', 'source' => 'manual']);

    expect($managerUser->can('view', $careSeeker))->toBeTrue();
});

test('super_admin can still view any care seeker', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $careSeeker = Family::factory()->create()->careSeekers()->create(['first_name' => 'Any', 'last_name' => 'Seeker']);

    expect($admin->can('view', $careSeeker))->toBeTrue();
});
