<?php

use App\Models\Advisor;
use App\Models\Family;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->user->id, 'is_active' => true]);
});

test('advisor can view the pipeline kanban board', function () {
    $family = Family::factory()->create();
    $this->advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.pipeline'));

    $response->assertOk();
});

test('pipeline groups leads by status into columns', function () {
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'new', 'source' => 'manual']);
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'contacted', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.pipeline'));

    $columns = $response->viewData('columns');
    expect($columns['new']->count())->toBe(1);
    expect($columns['contacted']->count())->toBe(1);
    expect($columns['shortlisted']->count())->toBe(0);
});

test('pipeline statistics reflect actual lead counts', function () {
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'converted', 'source' => 'manual']);
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'closed_lost', 'source' => 'manual']);
    $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'new', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->get(route('advisor.pipeline'));

    $stats = $response->viewData('stats');
    expect($stats['total'])->toBe(3);
    expect($stats['converted'])->toBe(1);
    expect($stats['closed_lost'])->toBe(1);
    expect($stats['open'])->toBe(1);
});

test('drag-and-drop move endpoint performs a legal stage transition', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->postJson(route('advisor.leads.move', $lead), ['status' => 'contacted']);

    $response->assertOk();
    $response->assertJson(['status' => 'contacted']);
    expect($lead->fresh()->status->value)->toBe('contacted');
});

test('drag-and-drop move endpoint rejects an illegal stage transition', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'new', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->postJson(route('advisor.leads.move', $lead), ['status' => 'converted']);

    $response->assertStatus(422);
    expect($lead->fresh()->status->value)->toBe('new');
});

test('drag-and-drop move to closed_lost requires a reason', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->postJson(route('advisor.leads.move', $lead), ['status' => 'closed_lost']);

    $response->assertStatus(422);
    $response->assertJson(['requires_reason' => true]);
});

test('drag-and-drop move to closed_lost succeeds with a reason', function () {
    $lead = $this->advisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->postJson(route('advisor.leads.move', $lead), [
        'status' => 'closed_lost', 'reason' => 'Chose a competitor',
    ]);

    $response->assertOk();
    expect($lead->fresh()->status->value)->toBe('closed_lost');
    expect($lead->fresh()->closed_reason)->toBe('Chose a competitor');
});

test('advisor cannot move a lead belonging to another advisor', function () {
    $otherAdvisor = Advisor::factory()->create();
    $lead = $otherAdvisor->leads()->create(['family_id' => Family::factory()->create()->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->user)->postJson(route('advisor.leads.move', $lead), ['status' => 'contacted']);

    $response->assertStatus(403);
});
