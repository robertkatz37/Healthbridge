<?php

use App\Models\Advisor;
use App\Models\Family;
use App\Models\Lead;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

test('advisor can view and update their own lead', function () {
    $user = User::factory()->create()->assignRole('advisor');
    $advisor = Advisor::create(['user_id' => $user->id, 'is_active' => true]);
    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'assigned', 'source' => 'manual', 'advisor_id' => $advisor->id]);

    expect($user->can('view', $lead))->toBeTrue();
    expect($user->can('update', $lead))->toBeTrue();
});

test('advisor cannot view a lead assigned to another advisor', function () {
    $user = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $user->id, 'is_active' => true]);

    $otherAdvisor = Advisor::factory()->create();
    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'assigned', 'source' => 'manual', 'advisor_id' => $otherAdvisor->id]);

    expect($user->can('view', $lead))->toBeFalse();
    expect($user->can('update', $lead))->toBeFalse();
});

test('advisor manager can view a lead assigned to their team member', function () {
    $managerUser = User::factory()->create()->assignRole('advisor_manager');
    $manager = Advisor::create(['user_id' => $managerUser->id, 'is_active' => true]);

    $teamMemberUser = User::factory()->create()->assignRole('advisor');
    $teamMember = Advisor::create(['user_id' => $teamMemberUser->id, 'is_active' => true, 'advisor_manager_id' => $manager->id]);

    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'assigned', 'source' => 'manual', 'advisor_id' => $teamMember->id]);

    expect($managerUser->can('view', $lead))->toBeTrue();
    expect($managerUser->can('update', $lead))->toBeTrue();
});

test('advisor manager cannot view a lead outside their team', function () {
    $managerUser = User::factory()->create()->assignRole('advisor_manager');
    Advisor::create(['user_id' => $managerUser->id, 'is_active' => true]);

    $unrelatedAdvisor = Advisor::factory()->create();
    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'assigned', 'source' => 'manual', 'advisor_id' => $unrelatedAdvisor->id]);

    expect($managerUser->can('view', $lead))->toBeFalse();
});

test('super_admin can view any lead', function () {
    $admin = User::factory()->create()->assignRole('super_admin');
    $lead = Lead::factory()->create();

    expect($admin->can('view', $lead))->toBeTrue();
});

test('platform_admin with leads.manage_all can view any lead', function () {
    $admin = User::factory()->create()->assignRole('platform_admin');
    $lead = Lead::factory()->create();

    expect($admin->can('view', $lead))->toBeTrue();
});

test('agency_owner cannot view leads at all', function () {
    $owner = User::factory()->create()->assignRole('agency_owner');
    $lead = Lead::factory()->create();

    expect($owner->can('viewAny', Lead::class))->toBeFalse();
    expect($owner->can('view', $lead))->toBeFalse();
});

test('family user cannot view leads at all', function () {
    $family = User::factory()->create()->assignRole('family');
    $lead = Lead::factory()->create();

    expect($family->can('viewAny', Lead::class))->toBeFalse();
    expect($family->can('view', $lead))->toBeFalse();
});

test('only leads.manage_all or advisor.manage_team can assign a lead', function () {
    $advisorUser = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $advisorUser->id, 'is_active' => true]);
    $lead = Lead::factory()->create();

    expect($advisorUser->can('assign', $lead))->toBeFalse();

    $admin = User::factory()->create()->assignRole('super_admin');
    expect($admin->can('assign', $lead))->toBeTrue();

    $manager = User::factory()->create()->assignRole('advisor_manager');
    expect($manager->can('assign', $lead))->toBeTrue();
});
