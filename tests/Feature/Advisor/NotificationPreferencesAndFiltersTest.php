<?php

use App\Models\Advisor;
use App\Models\CareSeeker;
use App\Models\Family;
use App\Models\User;
use App\Notifications\Advisor\LeadAssigned;
use App\Services\Advisor\LeadAssignmentService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->user->id, 'is_active' => true]);
});

// ─── Notification Preferences ──────────────────────────────────────────────

test('advisor can view notification preferences page', function () {
    $response = $this->actingAs($this->user)->get(route('advisor.notification-preferences.edit'));

    $response->assertOk();
});

test('advisor can turn off email notifications for lead assigned', function () {
    $this->actingAs($this->user)->put(route('advisor.notification-preferences.update'), [
        'lead_assigned_database' => '1',
    ]);

    $this->user->refresh();
    $service = app(\App\Services\Notifications\NotificationPreferenceService::class);
    expect($service->isEnabled($this->user, 'lead_assigned', 'mail'))->toBeFalse();
    expect($service->isEnabled($this->user, 'lead_assigned', 'database'))->toBeTrue();
});

test('default preferences are enabled for a user who never set any', function () {
    $service = app(\App\Services\Notifications\NotificationPreferenceService::class);

    expect($service->isEnabled($this->user, 'lead_assigned', 'mail'))->toBeTrue();
    expect($service->isEnabled($this->user, 'lead_assigned', 'database'))->toBeTrue();
});

test('LeadAssigned notification respects a disabled mail preference', function () {
    Notification::fake();
    app(\App\Services\Notifications\NotificationPreferenceService::class)->set($this->user, 'lead_assigned', 'mail', false);

    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'new', 'source' => 'manual']);
    app(LeadAssignmentService::class)->assign($lead, $this->advisor);

    Notification::assertSentTo($this->user, LeadAssigned::class, function ($notification, $channels) {
        return !in_array('mail', $channels) && in_array('database', $channels);
    });
});

test('LeadAssigned notification respects a disabled mail preference even if the advisor->user relation was already accessed earlier', function () {
    // Regression test: LeadAssignmentService::assign() previously used
    // $advisor->user->notify(...) directly. If something earlier in the
    // same request/test had already touched $advisor->user (loading and
    // caching that relation), a later preference change would not be
    // reflected — notify() would fire against the stale, pre-change User
    // instance still cached on the relation. Deliberately accessing the
    // relation here before the preference change reproduces that
    // scenario; assign() now queries the user fresh internally rather
    // than trusting $advisor->user, so this passes regardless.
    Notification::fake();
    $staleAccess = $this->advisor->user; // force-load and cache the relation
    expect($staleAccess->id)->toBe($this->user->id);

    app(\App\Services\Notifications\NotificationPreferenceService::class)->set($this->user, 'lead_assigned', 'mail', false);

    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'new', 'source' => 'manual']);
    app(LeadAssignmentService::class)->assign($lead, $this->advisor);

    Notification::assertSentTo($this->user, LeadAssigned::class, function ($notification, $channels) {
        return !in_array('mail', $channels) && in_array('database', $channels);
    });
});

// ─── Expanded Lead Search Filters ───────────────────────────────────────────

test('lead inbox can be filtered by care type', function () {
    $family1 = Family::factory()->create();
    $careSeeker1 = $family1->careSeekers()->create(['first_name' => 'A', 'last_name' => 'B', 'care_type_needed' => 'memory_care']);
    $this->advisor->leads()->create(['family_id' => $family1->id, 'care_seeker_id' => $careSeeker1->id, 'status' => 'assigned', 'source' => 'manual']);

    $family2 = Family::factory()->create();
    $careSeeker2 = $family2->careSeekers()->create(['first_name' => 'C', 'last_name' => 'D', 'care_type_needed' => 'assisted_living']);
    $this->advisor->leads()->create(['family_id' => $family2->id, 'care_seeker_id' => $careSeeker2->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.index', ['care_type' => 'memory_care']));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
});

test('lead inbox can be filtered by budget range', function () {
    $family1 = Family::factory()->create();
    $careSeeker1 = $family1->careSeekers()->create(['first_name' => 'A', 'last_name' => 'B', 'budget_min' => 2000, 'budget_max' => 3000]);
    $this->advisor->leads()->create(['family_id' => $family1->id, 'care_seeker_id' => $careSeeker1->id, 'status' => 'assigned', 'source' => 'manual']);

    $family2 = Family::factory()->create();
    $careSeeker2 = $family2->careSeekers()->create(['first_name' => 'C', 'last_name' => 'D', 'budget_min' => 5000, 'budget_max' => 6000]);
    $this->advisor->leads()->create(['family_id' => $family2->id, 'care_seeker_id' => $careSeeker2->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.index', ['budget_min' => 1500, 'budget_max' => 3500]));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
});

test('lead inbox can be filtered by territory city and state', function () {
    $lead1 = $this->advisor->leads()->create([
        'family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual',
        'territory_city' => 'Austin', 'territory_state' => 'TX',
    ]);
    $lead2 = $this->advisor->leads()->create([
        'family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual',
        'territory_city' => 'Denver', 'territory_state' => 'CO',
    ]);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.index', ['city' => 'Austin']));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
    expect($leads->first()->id)->toBe($lead1->id);
});

test('lead inbox can be filtered by move-in timeline', function () {
    $family1 = Family::factory()->create();
    $careSeeker1 = $family1->careSeekers()->create(['first_name' => 'A', 'last_name' => 'B', 'move_in_timeline' => 'immediately']);
    $this->advisor->leads()->create(['family_id' => $family1->id, 'care_seeker_id' => $careSeeker1->id, 'status' => 'assigned', 'source' => 'manual']);

    $family2 = Family::factory()->create();
    $careSeeker2 = $family2->careSeekers()->create(['first_name' => 'C', 'last_name' => 'D', 'move_in_timeline' => 'just_researching']);
    $this->advisor->leads()->create(['family_id' => $family2->id, 'care_seeker_id' => $careSeeker2->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.index', ['move_in_timeline' => 'immediately']));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
});

test('advisor manager can filter leads by team member advisor_id', function () {
    $managerUser = User::factory()->create()->assignRole('advisor_manager');
    $manager = Advisor::create(['user_id' => $managerUser->id, 'is_active' => true]);
    $memberUser = User::factory()->create()->assignRole('advisor');
    $member = Advisor::create(['user_id' => $memberUser->id, 'is_active' => true, 'advisor_manager_id' => $manager->id]);

    $manager->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $member->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($managerUser)->get(route('advisor.leads.index', ['advisor_id' => $member->id]));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
});

// ─── Expanded Dashboard KPIs ────────────────────────────────────────────────

test('dashboard summary includes every requested KPI key', function () {
    $summary = app(\App\Services\Advisor\AdvisorDashboardService::class)->summary($this->advisor);

    $expectedKeys = [
        'total_leads', 'open_leads', 'new_leads', 'active_leads', 'overdue_leads',
        'converted_leads', 'converted_this_month', 'conversion_rate', 'average_response_time_hours',
        'tasks_due_today', 'tasks_overdue', 'tasks_pending', 'tasks_completed',
        'tours_today', 'tours_upcoming',
    ];

    foreach ($expectedKeys as $key) {
        expect($summary)->toHaveKey($key);
    }
});

test('conversion rate is calculated correctly', function () {
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'converted', 'source' => 'manual']);
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'converted', 'source' => 'manual']);
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'new', 'source' => 'manual']);
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'new', 'source' => 'manual']);

    $summary = app(\App\Services\Advisor\AdvisorDashboardService::class)->summary($this->advisor);

    expect($summary['conversion_rate'])->toBe(50.0);
});

// ─── Document Access ────────────────────────────────────────────────────────

test('advisor assigned to a lead can download the care seekers documents', function () {
    Storage::fake('local');
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'A', 'last_name' => 'B']);
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'care_seeker_id' => $careSeeker->id, 'status' => 'assigned', 'source' => 'manual']);
    $path = 'care-seekers/1/documents/test.pdf';
    Storage::disk('local')->put($path, 'fake pdf content');
    $document = $careSeeker->documents()->create([
        'uploaded_by' => $family->user_id, 'document_type' => 'medical_record', 'path' => $path, 'original_filename' => 'test.pdf',
    ]);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.documents.download', [$lead, $document]));

    $response->assertOk();
});

test('advisor not assigned to the lead cannot download its documents', function () {
    Storage::fake('local');
    $otherAdvisor = Advisor::factory()->create();
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'A', 'last_name' => 'B']);
    $lead = $otherAdvisor->leads()->create(['family_id' => $family->id, 'care_seeker_id' => $careSeeker->id, 'status' => 'assigned', 'source' => 'manual']);
    $path = 'care-seekers/1/documents/test.pdf';
    Storage::disk('local')->put($path, 'fake pdf content');
    $document = $careSeeker->documents()->create([
        'uploaded_by' => $family->user_id, 'document_type' => 'medical_record', 'path' => $path, 'original_filename' => 'test.pdf',
    ]);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.documents.download', [$lead, $document]));

    $response->assertStatus(403);
});
