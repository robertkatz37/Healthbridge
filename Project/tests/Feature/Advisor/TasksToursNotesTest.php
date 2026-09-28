<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->user->id, 'is_active' => true]);
});

// ─── Tasks page ─────────────────────────────────────────────────────────────

test('advisor can view the dedicated tasks page', function () {
    $this->advisor->tasks()->create(['title' => 'Call family', 'due_at' => now()->addDay(), 'priority' => 'high']);

    $response = $this->actingAs($this->user)->get(route('advisor.tasks.index'));

    $response->assertOk();
    $response->assertSee('Call family');
});

test('tasks page filters by overdue', function () {
    $this->advisor->tasks()->create(['title' => 'Overdue task', 'due_at' => now()->subDay(), 'priority' => 'high']);
    $this->advisor->tasks()->create(['title' => 'Future task', 'due_at' => now()->addDay(), 'priority' => 'low']);

    $response = $this->actingAs($this->user)->get(route('advisor.tasks.index', ['filter' => 'overdue']));

    $tasks = $response->viewData('tasks');
    expect($tasks->total())->toBe(1);
});

test('tasks page filters by completed', function () {
    $task = $this->advisor->tasks()->create(['title' => 'Done task', 'due_at' => now()->addDay(), 'priority' => 'low', 'completed_at' => now()]);
    $this->advisor->tasks()->create(['title' => 'Pending task', 'due_at' => now()->addDay(), 'priority' => 'low']);

    $response = $this->actingAs($this->user)->get(route('advisor.tasks.index', ['filter' => 'completed']));

    $tasks = $response->viewData('tasks');
    expect($tasks->total())->toBe(1);
    expect($tasks->first()->id)->toBe($task->id);
});

test('advisor can create a standalone task with no lead', function () {
    $response = $this->actingAs($this->user)->post(route('advisor.tasks.store'), [
        'title' => 'General admin task', 'due_at' => now()->addDay()->toDateTimeString(), 'priority' => 'medium',
    ]);

    $response->assertRedirect();
    expect($this->advisor->tasks()->whereNull('lead_id')->count())->toBe(1);
});

test('advisor can edit a task', function () {
    $task = $this->advisor->tasks()->create(['title' => 'Old title', 'due_at' => now()->addDay(), 'priority' => 'low']);

    $response = $this->actingAs($this->user)->put(route('advisor.tasks.update', $task), [
        'title' => 'New title', 'due_at' => now()->addDays(2)->toDateTimeString(), 'priority' => 'high',
    ]);

    $response->assertRedirect();
    expect($task->fresh()->title)->toBe('New title');
    expect($task->fresh()->priority->value)->toBe('high');
});

test('advisor can reopen a completed task', function () {
    $task = $this->advisor->tasks()->create(['title' => 'Task', 'due_at' => now()->addDay(), 'priority' => 'low', 'completed_at' => now()]);

    $response = $this->actingAs($this->user)->post(route('advisor.tasks.reopen', $task));

    $response->assertRedirect();
    expect($task->fresh()->completed_at)->toBeNull();
});

test('advisor manager can manage a team members task', function () {
    $managerUser = User::factory()->create()->assignRole('advisor_manager');
    $manager = Advisor::create(['user_id' => $managerUser->id, 'is_active' => true]);
    $teamMemberUser = User::factory()->create()->assignRole('advisor');
    $teamMember = Advisor::create(['user_id' => $teamMemberUser->id, 'is_active' => true, 'advisor_manager_id' => $manager->id]);
    $task = $teamMember->tasks()->create(['title' => 'Team task', 'due_at' => now()->addDay(), 'priority' => 'low']);

    $response = $this->actingAs($managerUser)->post(route('advisor.tasks.complete', $task));

    $response->assertRedirect();
    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('advisor cannot edit a task belonging to an unrelated advisor', function () {
    $otherAdvisor = Advisor::factory()->create();
    $task = $otherAdvisor->tasks()->create(['title' => 'Not yours', 'due_at' => now()->addDay(), 'priority' => 'low']);

    $response = $this->actingAs($this->user)->put(route('advisor.tasks.update', $task), [
        'title' => 'Hijacked', 'due_at' => now()->addDay()->toDateTimeString(), 'priority' => 'low',
    ]);

    $response->assertStatus(403);
});

// ─── Tours page ──────────────────────────────────────────────────────────────

test('advisor can view the dedicated tours page', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $agency = Agency::factory()->create(['status' => 'published']);
    $lead->tourRequests()->create([
        'family_id' => $lead->family_id, 'agency_id' => $agency->id, 'requested_date' => now()->addDays(3)->toDateString(),
    ]);

    $response = $this->actingAs($this->user)->get(route('advisor.tours.index'));

    $response->assertOk();
});

test('advisor can reschedule a tour', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $agency = Agency::factory()->create(['status' => 'published']);
    $tour = $lead->tourRequests()->create([
        'family_id' => $lead->family_id, 'agency_id' => $agency->id, 'requested_date' => now()->addDays(3)->toDateString(),
    ]);
    $newAgency = Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($this->user)->put(route('advisor.tours.update', $tour), [
        'agency_id' => $newAgency->id, 'requested_date' => now()->addDays(7)->toDateString(),
    ]);

    $response->assertRedirect();
    expect($tour->fresh()->agency_id)->toBe($newAgency->id);
});

test('advisor cannot reschedule a tour on an unrelated advisors lead', function () {
    $otherAdvisor = Advisor::factory()->create();
    $lead = $otherAdvisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $agency = Agency::factory()->create(['status' => 'published']);
    $tour = $lead->tourRequests()->create([
        'family_id' => $lead->family_id, 'agency_id' => $agency->id, 'requested_date' => now()->addDays(3)->toDateString(),
    ]);

    $response = $this->actingAs($this->user)->put(route('advisor.tours.update', $tour), [
        'agency_id' => $agency->id, 'requested_date' => now()->addDays(10)->toDateString(),
    ]);

    $response->assertStatus(403);
});

// ─── Notes page ─────────────────────────────────────────────────────────────

test('advisor can view the dedicated notes page', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $lead->notes()->create(['advisor_id' => $this->advisor->id, 'family_id' => $lead->family_id, 'note_type' => 'call', 'content' => 'Test note content']);

    $response = $this->actingAs($this->user)->get(route('advisor.notes.index'));

    $response->assertOk();
    $response->assertSee('Test note content');
});

test('notes page filters internal comments separately', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $lead->notes()->create(['advisor_id' => $this->advisor->id, 'family_id' => $lead->family_id, 'note_type' => 'general', 'content' => 'Public note', 'is_internal' => false]);
    $lead->notes()->create(['advisor_id' => $this->advisor->id, 'family_id' => $lead->family_id, 'note_type' => 'general', 'content' => 'Private note', 'is_internal' => true]);

    $response = $this->actingAs($this->user)->get(route('advisor.notes.index', ['filter' => 'internal']));

    $notes = $response->viewData('notes');
    expect($notes->total())->toBe(1);
});

// ─── Inbox ──────────────────────────────────────────────────────────────────

test('advisor can view the communication inbox', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $conversation = $lead->conversations()->create();
    $conversation->messages()->create(['sender_id' => $this->user->id, 'body' => 'Hello there']);

    $response = $this->actingAs($this->user)->get(route('advisor.inbox'));

    $response->assertOk();
    $response->assertSee('Hello there');
});

// ─── Activity Timeline ──────────────────────────────────────────────────────

test('advisor can view the dedicated activity timeline page', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);
    $lead->notes()->create(['advisor_id' => $this->advisor->id, 'family_id' => $lead->family_id, 'note_type' => 'call', 'content' => 'Timeline test note']);

    $response = $this->actingAs($this->user)->get(route('advisor.activity'));

    $response->assertOk();
});

test('activity timeline aggregates status changes notes tasks and tours', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'new', 'source' => 'manual']);
    app(\App\Services\Advisor\LeadPipelineService::class)->transition($lead, \App\Enums\LeadStatus::Assigned);
    $lead->notes()->create(['advisor_id' => $this->advisor->id, 'family_id' => $lead->family_id, 'note_type' => 'call', 'content' => 'A note']);
    $this->advisor->tasks()->create(['lead_id' => $lead->id, 'title' => 'A task', 'due_at' => now()->addDay(), 'priority' => 'low']);

    $timeline = app(\App\Services\Advisor\LeadTimelineService::class)->forAdvisor($this->advisor);

    expect($timeline->pluck('type'))->toContain('status_change');
    expect($timeline->pluck('type'))->toContain('note');
    expect($timeline->pluck('type'))->toContain('task_created');
});
