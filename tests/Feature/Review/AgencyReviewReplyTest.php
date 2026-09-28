<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\Referral;
use App\Models\User;
use App\Services\Review\ReviewModerationService;
use App\Services\Review\ReviewSubmissionService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->advisorUser = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->advisorUser->id, 'is_active' => true]);
    $this->family = Family::factory()->create();
    $this->familyUser = $this->family->user;
    $this->familyUser->assignRole('family');
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $this->lead = $this->family->leads()->create(['care_seeker_id' => $this->careSeeker->id, 'advisor_id' => $this->advisor->id, 'status' => 'assigned', 'source' => 'manual']);
    $this->agencyUser = User::factory()->create()->assignRole('agency_owner');
    $this->agency = Agency::factory()->create(['status' => 'published', 'user_id' => $this->agencyUser->id]);
    $this->referral = Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'converted']);
    $this->review = app(ReviewSubmissionService::class)->submit($this->family, $this->referral, 'Great', 'Wonderful experience overall', ['staff' => 5], true, false);
    app(ReviewModerationService::class)->approve($this->review);
});

test('agency can view their reviews page', function () {
    $response = $this->actingAs($this->agencyUser)->get(route('agency.reviews.index'));

    $response->assertOk();
    $response->assertSee('Great');
});

test('agency can post a reply to a review', function () {
    $response = $this->actingAs($this->agencyUser)->post(route('agency.reviews.reply', $this->review), [
        'body' => 'Thank you so much for the kind words!',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect($this->review->fresh()->reply->body)->toBe('Thank you so much for the kind words!');

    Notification::assertSentTo($this->familyUser, \App\Notifications\Review\AgencyResponseAdded::class);
});

test('editing a reply preserves the previous version in history', function () {
    $this->actingAs($this->agencyUser)->post(route('agency.reviews.reply', $this->review), ['body' => 'Original response.']);
    $this->actingAs($this->agencyUser)->post(route('agency.reviews.reply', $this->review), ['body' => 'Updated response.']);

    $reply = $this->review->fresh()->reply;
    expect($reply->body)->toBe('Updated response.');
    expect($reply->edited_at)->not->toBeNull();
    expect($reply->revisions()->count())->toBe(1);
    expect($reply->revisions()->first()->body)->toBe('Original response.');
});

test('a different agency cannot reply to a review that is not theirs', function () {
    $otherAgencyUser = User::factory()->create()->assignRole('agency_owner');
    Agency::factory()->create(['status' => 'published', 'user_id' => $otherAgencyUser->id]);

    $response = $this->actingAs($otherAgencyUser)->post(route('agency.reviews.reply', $this->review), [
        'body' => 'Trying to reply to someone elses review.',
    ]);

    $response->assertStatus(403);
});
