<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->user = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->user->id, 'is_active' => true]);
});

test('advisor can view their lead inbox', function () {
    $family = Family::factory()->create();
    $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.index'));

    $response->assertOk();
});

test('lead inbox only shows the advisors own leads', function () {
    $family = Family::factory()->create();
    $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $otherAdvisor = Advisor::factory()->create();
    $otherAdvisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.index'));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
});

test('lead inbox can be filtered by status', function () {
    $family = Family::factory()->create();
    $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'contacted', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.index', ['status' => 'contacted']));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
});

test('lead inbox can be searched by family name', function () {
    $familyUser = User::factory()->create(['name' => 'Findable Family']);
    $family = Family::create(['user_id' => $familyUser->id]);
    $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.index', ['search' => 'Findable']));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
});

test('advisor can view lead details', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.show', $lead));

    $response->assertOk();
});

test('advisor can create a manual lead which gets auto-assigned', function () {
    $family = Family::factory()->create();

    $response = $this->actingAs($this->user)->post(route('advisor.leads.store'), [
        'family_id' => $family->id,
        'source' => 'manual',
    ]);

    $response->assertRedirect();
    expect(\App\Models\Lead::where('family_id', $family->id)->exists())->toBeTrue();
});

test('advisor can transition a lead status', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->put(route('advisor.leads.status', $lead), ['status' => 'contacted']);

    $response->assertRedirect();
    expect($lead->fresh()->status->value)->toBe('contacted');
});

test('closing a lead as lost requires a reason', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->put(route('advisor.leads.status', $lead), ['status' => 'closed_lost']);

    $response->assertSessionHasErrors('reason');
});

// ─── Notes ──────────────────────────────────────────────────────────────────

test('advisor can add a lead note', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->post(route('advisor.leads.notes.store', $lead), [
        'note_type' => 'call', 'content' => 'Called the family, left voicemail.',
    ]);

    $response->assertRedirect();
    expect($lead->notes()->count())->toBe(1);
});

test('internal comment flag is stored correctly', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $this->actingAs($this->user)->post(route('advisor.leads.notes.store', $lead), [
        'note_type' => 'general', 'content' => 'Staff-only remark', 'is_internal' => '1',
    ]);

    expect($lead->notes()->first()->is_internal)->toBeTrue();
});

// ─── Tasks ──────────────────────────────────────────────────────────────────

test('advisor can add and complete a task', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $this->actingAs($this->user)->post(route('advisor.tasks.store'), [
        'lead_id' => $lead->id, 'title' => 'Follow up call', 'due_at' => now()->addDay()->toDateTimeString(), 'priority' => 'medium',
    ]);
    $task = $lead->tasks()->first();

    $response = $this->actingAs($this->user)->post(route('advisor.tasks.complete', $task));

    $response->assertRedirect();
    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('advisor cannot complete another advisors task', function () {
    $otherAdvisor = Advisor::factory()->create();
    $task = $otherAdvisor->tasks()->create(['title' => 'Not yours', 'due_at' => now()->addDay()]);

    $response = $this->actingAs($this->user)->post(route('advisor.tasks.complete', $task));

    $response->assertStatus(403);
});

// ─── Tours ──────────────────────────────────────────────────────────────────

test('advisor can schedule a tour for a lead', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);
    $agency = Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($this->user)->post(route('advisor.leads.tours.store', $lead), [
        'agency_id' => $agency->id,
        'requested_date' => now()->addDays(3)->toDateString(),
    ]);

    $response->assertRedirect();
    expect($lead->tourRequests()->count())->toBe(1);
});

test('scheduling a tour in the past is rejected', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);
    $agency = Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($this->user)->post(route('advisor.leads.tours.store', $lead), [
        'agency_id' => $agency->id,
        'requested_date' => now()->subDay()->toDateString(),
    ]);

    $response->assertSessionHasErrors('requested_date');
});

// ─── Conversations ──────────────────────────────────────────────────────────

test('advisor can send a message to the family within a lead', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->post(route('advisor.leads.conversation.store', $lead), [
        'body' => 'Hello, following up on your care search.',
    ]);

    $response->assertRedirect();
    expect($lead->conversations()->first()->messages()->count())->toBe(1);
});

test('advisor can view the conversation thread', function () {
    $family = Family::factory()->create();
    $lead = $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.leads.conversation', $lead));

    $response->assertOk();
});
