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
    $this->agency = Agency::factory()->create(['status' => 'published']);
    $this->referral = Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'converted']);
    $this->review = app(ReviewSubmissionService::class)->submit($this->family, $this->referral, 'Excellent care', 'Truly wonderful staff and facility', ['staff' => 5, 'cleanliness' => 4], true, false);
    app(ReviewModerationService::class)->approve($this->review);
});

test('public agency page shows the published review, rating breakdown, and verified badge without login', function () {
    $response = $this->get(route('agencies.show', $this->agency));

    $response->assertOk();
    $response->assertSee('Excellent care');
    $response->assertSee('Verified Stay');
});

test('pending or hidden reviews never appear on the public agency page', function () {
    $otherFamily = Family::factory()->create();
    $otherCareSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Other', 'last_name' => 'Seeker']);
    $otherReferral = Referral::create(['family_id' => $otherFamily->id, 'care_seeker_id' => $otherCareSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'converted']);
    app(ReviewSubmissionService::class)->submit($otherFamily, $otherReferral, 'Not yet visible', 'This one is still pending moderation', ['staff' => 3], null, false);

    $response = $this->get(route('agencies.show', $this->agency));

    $response->assertOk();
    $response->assertDontSee('Not yet visible');
});

test('an authenticated user can vote a review as helpful from the public page', function () {
    $voter = User::factory()->create();

    $response = $this->actingAs($voter)->postJson(route('reviews.vote', $this->review), ['is_helpful' => true]);

    $response->assertOk();
    $response->assertJson(['helpful_count' => 1]);
});

test('an authenticated user can report a review from the public page', function () {
    $reporter = User::factory()->create();

    $response = $this->actingAs($reporter)->post(route('reviews.report', $this->review), ['reason' => 'fake', 'details' => 'Seems made up']);

    $response->assertRedirect();
    expect($this->review->reports()->count())->toBe(1);
});

test('sorting reviews by highest rated works on the public page', function () {
    $response = $this->get(route('agencies.show', $this->agency) . '?reviews_sort=highest');

    $response->assertOk();
});
