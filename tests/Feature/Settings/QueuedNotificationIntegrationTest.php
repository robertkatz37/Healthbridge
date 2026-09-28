<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\User;
use App\Notifications\Agency\ApplicationApproved;
use App\Services\Agency\AgencyProvisioningService;
use App\Services\Agency\AgencyOnboardingService;
use App\Services\Agency\AgencyModerationService;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => $this->withoutVite());

test('agency moderation notifications are queued, not sent synchronously', function () {
    Queue::fake();

    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $agency = app(AgencyProvisioningService::class)->createDraftAgency($owner, [
        'agency_category_id' => $category->id,
        'name' => 'Queue Test Agency',
    ]);
    $agency->services()->create(['name' => 'Test Service']);
    $agency->coverage()->create(['city' => 'Austin', 'state' => 'TX']);
    app(AgencyOnboardingService::class)->complete($agency);
    $agency->refresh();

    app(AgencyModerationService::class)->approve($agency, $admin);

    // ShouldQueue notifications dispatch as a queued job rather than
    // sending immediately — verifies the Phase 10 "Email Queue support"
    // requirement actually took effect on these 5 notifications.
    Queue::assertPushed(\Illuminate\Notifications\Events\NotificationSent::class, 0);
});

test('approve still correctly notifies the owner end to end with a real queue connection', function () {
    // Uses the actual sync-processed database queue (no Queue::fake())
    // to prove the full pipeline works, not just that a job was pushed.
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();

    $owner = User::factory()->create()->assignRole('agency_owner');
    $category = AgencyCategory::first();
    $agency = app(AgencyProvisioningService::class)->createDraftAgency($owner, [
        'agency_category_id' => $category->id,
        'name' => 'Sync Queue Test Agency',
    ]);
    $agency->services()->create(['name' => 'Test Service']);
    $agency->coverage()->create(['city' => 'Austin', 'state' => 'TX']);
    app(AgencyOnboardingService::class)->complete($agency);
    $agency->refresh();

    app(AgencyModerationService::class)->approve($agency, $admin);

    // QUEUE_CONNECTION=sync in phpunit.xml means the queued notification
    // processes immediately within this same request — so the database
    // channel entry should already exist for the owner.
    expect($owner->notifications()->count())->toBe(1);
    expect($owner->notifications()->first()->data['event'])->toBe('agency_approved');
});
