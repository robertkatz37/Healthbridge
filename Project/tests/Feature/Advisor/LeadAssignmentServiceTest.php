<?php

use App\Models\Advisor;
use App\Models\Family;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\Advisor\LeadAssigned;
use App\Services\Advisor\LeadAssignmentService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->service = app(LeadAssignmentService::class);
});

function makeAdvisor(): Advisor
{
    $user = User::factory()->create();
    $user->assignRole('advisor');
    return Advisor::create(['user_id' => $user->id, 'is_active' => true]);
}

function makeLead(?int $careSeekerId = null): Lead
{
    $familyUser = User::factory()->create();
    $familyUser->assignRole('family');
    $family = Family::create(['user_id' => $familyUser->id]);

    return $family->leads()->create([
        'status' => 'new',
        'source' => 'manual',
        'care_seeker_id' => $careSeekerId,
    ]);
}

test('round robin cycles through active advisors in order', function () {
    $advisor1 = makeAdvisor();
    $advisor2 = makeAdvisor();

    $lead1 = makeLead();
    $lead2 = makeLead();

    $assigned1 = $this->service->autoAssign($lead1);
    $assigned2 = $this->service->autoAssign($lead2);

    expect($assigned1->id)->not->toBe($assigned2->id);
});

test('round robin skips inactive advisors', function () {
    $active = makeAdvisor();
    $inactiveUser = User::factory()->create()->assignRole('advisor');
    Advisor::create(['user_id' => $inactiveUser->id, 'is_active' => false]);

    $lead = makeLead();
    $assigned = $this->service->autoAssign($lead);

    expect($assigned->id)->toBe($active->id);
});

test('territory-based assignment matches a care seekers preferred location', function () {
    $advisor1 = makeAdvisor();
    $advisor2 = makeAdvisor();
    $advisor2->territories()->create(['city' => 'Austin', 'state' => 'TX', 'radius_miles' => 25]);

    $familyUser = User::factory()->create()->assignRole('family');
    $family = Family::create(['user_id' => $familyUser->id]);
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Jane', 'last_name' => 'Doe',
        'preferred_city' => 'Austin', 'preferred_state' => 'TX',
    ]);
    $lead = $family->leads()->create(['status' => 'new', 'source' => 'manual', 'care_seeker_id' => $careSeeker->id]);

    $assigned = $this->service->autoAssign($lead);

    expect($assigned->id)->toBe($advisor2->id);
});

test('falls back to round robin when no advisor covers the territory', function () {
    $advisor = makeAdvisor();

    $familyUser = User::factory()->create()->assignRole('family');
    $family = Family::create(['user_id' => $familyUser->id]);
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Jane', 'last_name' => 'Doe',
        'preferred_city' => 'Nowhere', 'preferred_state' => 'ZZ',
    ]);
    $lead = $family->leads()->create(['status' => 'new', 'source' => 'manual', 'care_seeker_id' => $careSeeker->id]);

    $assigned = $this->service->autoAssign($lead);

    expect($assigned->id)->toBe($advisor->id);
});

test('assignment transitions a new lead to assigned status', function () {
    $advisor = makeAdvisor();
    $lead = makeLead();

    $this->service->autoAssign($lead);
    $lead->refresh();

    expect($lead->status->value)->toBe('assigned');
    expect($lead->assigned_at)->not->toBeNull();
});

test('assignment creates an assignment history row', function () {
    $advisor = makeAdvisor();
    $lead = makeLead();

    $this->service->autoAssign($lead);

    expect($lead->assignmentHistory()->count())->toBe(1);
    expect($lead->assignmentHistory()->first()->family_id)->toBe($lead->family_id);
});

test('reassignment closes out the prior assignment history row', function () {
    $advisor1 = makeAdvisor();
    $advisor2 = makeAdvisor();
    $lead = makeLead();

    $this->service->assign($lead, $advisor1);
    $this->service->assign($lead, $advisor2);

    expect($lead->assignmentHistory()->count())->toBe(2);
    expect($lead->assignmentHistory()->whereNotNull('unassigned_at')->count())->toBe(1);
    expect($lead->fresh()->advisor_id)->toBe($advisor2->id);
});

test('assigning a lead notifies the advisor', function () {
    $advisor = makeAdvisor();
    $lead = makeLead();

    $this->service->autoAssign($lead);

    Notification::assertSentTo($advisor->user, LeadAssigned::class);
});

test('territory snapshot is stored on the lead at assignment time', function () {
    $advisor = makeAdvisor();
    $advisor->territories()->create(['city' => 'Denver', 'state' => 'CO']);

    $familyUser = User::factory()->create()->assignRole('family');
    $family = Family::create(['user_id' => $familyUser->id]);
    $careSeeker = $family->careSeekers()->create([
        'first_name' => 'Jane', 'last_name' => 'Doe',
        'preferred_city' => 'Denver', 'preferred_state' => 'CO',
    ]);
    $lead = $family->leads()->create(['status' => 'new', 'source' => 'manual', 'care_seeker_id' => $careSeeker->id]);

    $this->service->autoAssign($lead);
    $lead->refresh();

    expect($lead->territory_city)->toBe('Denver');
    expect($lead->territory_state)->toBe('CO');
});
