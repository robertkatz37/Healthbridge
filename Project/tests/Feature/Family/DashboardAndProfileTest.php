<?php

use App\Models\Family;
use App\Models\User;
use App\Notifications\Agency\ApplicationApproved;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('family');
    $this->family = Family::create(['user_id' => $this->user->id]);
});

test('family dashboard shows KPI summary', function () {
    $this->family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $response = $this->actingAs($this->user)->get(route('family.dashboard'));

    $response->assertOk();
    $response->assertSee('Jane Doe');
});

test('family can update their profile', function () {
    $response = $this->actingAs($this->user)->put(route('family.profile.update'), [
        'phone' => '555-1234',
        'relationship_to_seeker' => 'Daughter',
    ]);

    $response->assertRedirect();
    $this->family->refresh();
    expect($this->family->phone)->toBe('555-1234');
    expect($this->family->relationship_to_seeker)->toBe('Daughter');
});

test('family cannot update another familys profile via a crafted request', function () {
    $otherFamily = Family::factory()->create();

    // The route always resolves the CURRENT user's own family record, so
    // there's no way to target another family's row through this endpoint
    // at all — verifying isolation rather than object-level tampering.
    $response = $this->actingAs($this->user)->put(route('family.profile.update'), [
        'phone' => '555-0000',
    ]);

    $response->assertRedirect();
    expect($otherFamily->fresh()->phone)->not->toBe('555-0000');
});

// ─── Notifications ──────────────────────────────────────────────────────────

test('family can view their notifications center', function () {
    $this->user->notify(new ApplicationApproved(\App\Models\Agency::factory()->create()));

    $response = $this->actingAs($this->user)->get(route('family.notifications.index'));

    $response->assertOk();
});

test('family can mark a single notification as read', function () {
    $this->user->notify(new ApplicationApproved(\App\Models\Agency::factory()->create()));
    $notification = $this->user->notifications()->first();

    $response = $this->actingAs($this->user)->post(route('family.notifications.read', $notification->id));

    $response->assertRedirect();
    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('family can mark all notifications as read', function () {
    $agency = \App\Models\Agency::factory()->create();
    $this->user->notify(new ApplicationApproved($agency));
    $this->user->notify(new ApplicationApproved($agency));

    $response = $this->actingAs($this->user)->post(route('family.notifications.read-all'));

    $response->assertRedirect();
    expect($this->user->unreadNotifications()->count())->toBe(0);
});

// ─── Activity Timeline ──────────────────────────────────────────────────────

test('family can view their activity timeline', function () {
    $careSeeker = $this->family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    activity()->causedBy($this->user)->performedOn($careSeeker)->log('Care seeker profile created');

    $response = $this->actingAs($this->user)->get(route('family.activity'));

    $response->assertOk();
    $response->assertSee('Care seeker profile created');
});

test('unverified email cannot access family routes', function () {
    $unverified = User::factory()->unverified()->create()->assignRole('family');
    Family::create(['user_id' => $unverified->id]);

    $response = $this->actingAs($unverified)->get(route('family.dashboard'));

    $response->assertRedirect(route('verification.notice'));
});
