<?php

use App\Models\Advisor;
use App\Models\Family;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('super_admin can view all leads platform-wide', function () {
    $advisor = Advisor::factory()->create();
    $family = Family::factory()->create();
    $advisor->leads()->create(['family_id' => $family->id, 'status' => 'assigned', 'source' => 'manual']);

    $response = $this->actingAs($this->admin)->get(route('admin.leads.index'));

    $response->assertOk();
});

test('super_admin can manually assign an unassigned lead', function () {
    $advisor = Advisor::factory()->create(['is_active' => true]);
    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'new', 'source' => 'website_inquiry']);

    $response = $this->actingAs($this->admin)->post(route('admin.leads.assign', $lead), [
        'advisor_id' => $advisor->id,
    ]);

    $response->assertRedirect();
    expect($lead->fresh()->advisor_id)->toBe($advisor->id);
});

test('super_admin can reassign an already-assigned lead', function () {
    $advisor1 = Advisor::factory()->create(['is_active' => true]);
    $advisor2 = Advisor::factory()->create(['is_active' => true]);
    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'assigned', 'source' => 'manual', 'advisor_id' => $advisor1->id]);

    $response = $this->actingAs($this->admin)->post(route('admin.leads.assign', $lead), [
        'advisor_id' => $advisor2->id,
    ]);

    $response->assertRedirect();
    expect($lead->fresh()->advisor_id)->toBe($advisor2->id);
});

test('platform_admin with leads.manage_all can view and assign leads', function () {
    $platformAdmin = User::factory()->create()->assignRole('platform_admin');
    $advisor = Advisor::factory()->create(['is_active' => true]);
    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'new', 'source' => 'manual']);

    $indexResponse = $this->actingAs($platformAdmin)->get(route('admin.leads.index'));
    $indexResponse->assertOk();

    $assignResponse = $this->actingAs($platformAdmin)->post(route('admin.leads.assign', $lead), [
        'advisor_id' => $advisor->id,
    ]);
    $assignResponse->assertRedirect();
    expect($lead->fresh()->advisor_id)->toBe($advisor->id);
});

test('moderator without leads.manage_all cannot access admin lead management', function () {
    $moderator = User::factory()->create()->assignRole('moderator');
    $lead = Lead::factory()->create();

    $this->actingAs($moderator)->get(route('admin.leads.index'))->assertStatus(403);
    $this->actingAs($moderator)->get(route('admin.leads.show', $lead))->assertStatus(403);
});

test('leads can be filtered to unassigned only', function () {
    $family1 = Family::factory()->create();
    $family2 = Family::factory()->create();
    $advisor = Advisor::factory()->create();
    $unassigned = $family1->leads()->create(['status' => 'new', 'source' => 'manual']);
    $assigned = $family2->leads()->create(['status' => 'assigned', 'source' => 'manual', 'advisor_id' => $advisor->id]);

    $response = $this->actingAs($this->admin)->get(route('admin.leads.index', ['unassigned' => 1]));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
    expect($leads->first()->id)->toBe($unassigned->id);
});

test('assigning a nonexistent advisor fails validation', function () {
    $family = Family::factory()->create();
    $lead = $family->leads()->create(['status' => 'new', 'source' => 'manual']);

    $response = $this->actingAs($this->admin)->post(route('admin.leads.assign', $lead), [
        'advisor_id' => 999999,
    ]);

    $response->assertSessionHasErrors('advisor_id');
});
