<?php

use App\Models\AgencyCategory;
use App\Models\User;
use App\Notifications\Agency\ApplicationApproved;
use App\Notifications\Agency\ApplicationRejected;
use App\Notifications\Agency\ChangesRequested;
use App\Services\Agency\AgencyOnboardingService;
use App\Services\Agency\AgencyProvisioningService;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();

    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();

    $this->owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, [
        'agency_category_id' => $category->id,
        'name' => 'Test Agency',
    ]);
    $this->agency->services()->create(['name' => 'Personal Care']);
    $this->agency->coverage()->create(['city' => 'Austin', 'state' => 'TX']);
    app(AgencyOnboardingService::class)->complete($this->agency);
    $this->agency->refresh();
});

test('admin can view the applications queue', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.agencies.index'));

    $response->assertOk();
    $response->assertSee('Test Agency');
});

test('queue defaults to showing only pending review and changes requested agencies', function () {
    $published = \App\Models\Agency::factory()->create(['status' => 'published', 'name' => 'Already Live Agency']);

    $response = $this->actingAs($this->admin)->get(route('admin.agencies.index'));

    $response->assertSee('Test Agency');
    $response->assertDontSee('Already Live Agency');
});

test('family user cannot access the applications queue', function () {
    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->get(route('admin.agencies.index'));

    $response->assertStatus(403);
});

test('moderator role can access the applications queue', function () {
    $moderator = User::factory()->create()->assignRole('moderator');

    $response = $this->actingAs($moderator)->get(route('admin.agencies.index'));

    $response->assertOk();
});

test('admin can view a single agency application detail', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.agencies.show', $this->agency));

    $response->assertOk();
    $response->assertSee('Test Agency');
    $response->assertSee('Personal Care');
});

test('admin can approve an agency and the owner is notified', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.agencies.approve', $this->agency));

    $response->assertRedirect(route('admin.agencies.show', $this->agency));
    expect($this->agency->fresh()->status->value)->toBe('published');

    Notification::assertSentTo($this->owner, ApplicationApproved::class);
});

test('admin can reject an agency with a reason and the owner is notified', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.agencies.reject', $this->agency), [
        'reason' => 'Missing required insurance documentation.',
    ]);

    $response->assertRedirect();
    expect($this->agency->fresh()->status->value)->toBe('rejected');

    Notification::assertSentTo($this->owner, ApplicationRejected::class);
});

test('reject requires a reason of at least 10 characters', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.agencies.reject', $this->agency), [
        'reason' => 'too short',
    ]);

    $response->assertSessionHasErrors('reason');
    expect($this->agency->fresh()->status->value)->toBe('pending_review');
});

test('admin can request changes with notes and the owner is notified', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.agencies.request-changes', $this->agency), [
        'notes' => 'Please add your state license number.',
    ]);

    $response->assertRedirect();
    expect($this->agency->fresh()->status->value)->toBe('changes_requested');

    Notification::assertSentTo($this->owner, ChangesRequested::class);
});

test('admin can suspend a published agency', function () {
    $this->actingAs($this->admin)->post(route('admin.agencies.approve', $this->agency));

    $response = $this->actingAs($this->admin)->post(route('admin.agencies.suspend', $this->agency), [
        'reason' => 'Documentation expired and needs renewal.',
    ]);

    $response->assertRedirect();
    expect($this->agency->fresh()->status->value)->toBe('suspended');
});

test('admin can reactivate a suspended agency', function () {
    $this->actingAs($this->admin)->post(route('admin.agencies.approve', $this->agency));
    $this->actingAs($this->admin)->post(route('admin.agencies.suspend', $this->agency), [
        'reason' => 'Documentation expired and needs renewal.',
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.agencies.reactivate', $this->agency));

    $response->assertRedirect();
    expect($this->agency->fresh()->status->value)->toBe('published');
});

test('agency_owner cannot approve their own agency', function () {
    $response = $this->actingAs($this->owner)->post(route('admin.agencies.approve', $this->agency));

    $response->assertStatus(403);
});

test('admin can add an internal note visible only in the admin panel', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.agencies.notes.store', $this->agency), [
        'note' => 'Spoke with owner, all documents in order.',
    ]);

    $response->assertRedirect();
    expect($this->agency->adminNotes()->count())->toBe(1);
});

test('internal admin notes never appear on the owner-facing dashboard', function () {
    app(\App\Services\Agency\AgencyModerationService::class)
        ->addNote($this->agency, $this->admin, 'Confidential reviewer note.');

    $this->agency->update(['status' => 'published']);

    $response = $this->actingAs($this->owner)->get(route('agency.dashboard'));

    $response->assertOk();
    $response->assertDontSee('Confidential reviewer note.');
});

test('admin dashboard shows the pending review count', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

    $response->assertOk();
});

test('applications queue can be filtered by status', function () {
    $this->actingAs($this->admin)->post(route('admin.agencies.approve', $this->agency));

    $response = $this->actingAs($this->admin)->get(route('admin.agencies.index', ['status' => 'published']));

    $response->assertOk();
    $response->assertSee('Test Agency');
});
