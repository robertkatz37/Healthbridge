<?php

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\User;
use App\Services\Agency\AgencyModerationService;
use App\Services\Agency\AgencyOnboardingService;
use App\Services\Agency\AgencyProvisioningService;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

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

    $this->moderation = app(AgencyModerationService::class);
    $this->onboarding = app(AgencyOnboardingService::class);
});

test('approve transitions pending_review directly to published via approved', function () {
    $this->onboarding->complete($this->agency);
    $this->agency->refresh();

    $this->moderation->approve($this->agency, $this->admin);
    $this->agency->refresh();

    expect($this->agency->status)->toBe(AgencyStatus::Published);

    $history = $this->agency->statusHistory()->orderBy('id')->pluck('to_status')->toArray();
    expect($history)->toBe(['pending_review', 'approved', 'published']);
});

test('approve cannot be called on a draft agency', function () {
    expect(fn () => $this->moderation->approve($this->agency, $this->admin))
        ->toThrow(ValidationException::class);

    expect($this->agency->fresh()->status)->toBe(AgencyStatus::Draft);
});

test('reject requires the agency to be in pending_review', function () {
    $this->onboarding->complete($this->agency);
    $this->agency->refresh();

    $this->moderation->reject($this->agency, $this->admin, 'Insufficient documentation provided.');
    $this->agency->refresh();

    expect($this->agency->status)->toBe(AgencyStatus::Rejected);
});

test('rejected is a terminal status with no further transitions', function () {
    $this->onboarding->complete($this->agency);
    $this->agency->refresh();
    $this->moderation->reject($this->agency, $this->admin, 'Insufficient documentation provided.');
    $this->agency->refresh();

    expect($this->agency->status->allowedNextStatuses())->toBe([]);
});

test('request changes moves agency to changes_requested with the note recorded', function () {
    $this->onboarding->complete($this->agency);
    $this->agency->refresh();

    $this->moderation->requestChanges($this->agency, $this->admin, 'Please upload your business license.');
    $this->agency->refresh();

    expect($this->agency->status)->toBe(AgencyStatus::ChangesRequested);

    $entry = $this->agency->statusHistory()->where('to_status', 'changes_requested')->first();
    expect($entry->reason)->toBe('Please upload your business license.');
});

test('owner can resubmit after changes requested, returning to pending_review', function () {
    $this->onboarding->complete($this->agency);
    $this->agency->refresh();
    $this->moderation->requestChanges($this->agency, $this->admin, 'Fix your address.');
    $this->agency->refresh();

    $this->onboarding->complete($this->agency);
    $this->agency->refresh();

    expect($this->agency->status)->toBe(AgencyStatus::PendingReview);
});

test('cannot resubmit an already-published agency', function () {
    $this->onboarding->complete($this->agency);
    $this->agency->refresh();
    $this->moderation->approve($this->agency, $this->admin);
    $this->agency->refresh();

    expect(fn () => $this->onboarding->complete($this->agency))
        ->toThrow(ValidationException::class);
});

test('suspend removes a published agency from public visibility', function () {
    $this->onboarding->complete($this->agency);
    $this->agency->refresh();
    $this->moderation->approve($this->agency, $this->admin);
    $this->agency->refresh();

    expect(Agency::published()->where('id', $this->agency->id)->exists())->toBeTrue();

    $this->moderation->suspend($this->agency, $this->admin, 'Insurance expired.');
    $this->agency->refresh();

    expect(Agency::published()->where('id', $this->agency->id)->exists())->toBeFalse();
    expect($this->agency->status)->toBe(AgencyStatus::Suspended);
});

test('reactivate restores a suspended agency to published without re-review', function () {
    $this->onboarding->complete($this->agency);
    $this->agency->refresh();
    $this->moderation->approve($this->agency, $this->admin);
    $this->agency->refresh();
    $this->moderation->suspend($this->agency, $this->admin, 'Temporary issue.');
    $this->agency->refresh();

    $this->moderation->reactivate($this->agency, $this->admin);
    $this->agency->refresh();

    expect($this->agency->status)->toBe(AgencyStatus::Published);
    expect(Agency::published()->where('id', $this->agency->id)->exists())->toBeTrue();
});

test('cannot suspend a draft agency', function () {
    expect(fn () => $this->moderation->suspend($this->agency, $this->admin, 'reason'))
        ->toThrow(ValidationException::class);
});

test('internal admin note is created and attributed to its author', function () {
    $note = $this->moderation->addNote($this->agency, $this->admin, 'Called owner, very responsive.');

    expect($note->note)->toBe('Called owner, very responsive.');
    expect($note->user_id)->toBe($this->admin->id);
    expect($this->agency->adminNotes()->count())->toBe(1);
});

test('every moderation action is logged in the activity log', function () {
    $this->onboarding->complete($this->agency);
    $this->agency->refresh();
    $this->moderation->approve($this->agency, $this->admin);

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $this->admin->id,
        'description' => 'Agency approved and published',
    ]);
});
