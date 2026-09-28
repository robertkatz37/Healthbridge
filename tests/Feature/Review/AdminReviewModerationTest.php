<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\Referral;
use App\Models\Review;
use App\Models\User;
use App\Services\Review\ReviewReportService;
use App\Services\Review\ReviewSubmissionService;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();

    $advisorUser = User::factory()->create()->assignRole('advisor');
    $advisor = Advisor::create(['user_id' => $advisorUser->id, 'is_active' => true]);
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $lead = $family->leads()->create(['care_seeker_id' => $careSeeker->id, 'advisor_id' => $advisor->id, 'status' => 'assigned', 'source' => 'manual']);
    $this->agency = Agency::factory()->create(['status' => 'published']);
    $referral = Referral::create(['family_id' => $family->id, 'care_seeker_id' => $careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $advisor->id, 'lead_id' => $lead->id, 'status' => 'converted']);
    $this->review = app(ReviewSubmissionService::class)->submit($family, $referral, 'Great stay', 'Really wonderful experience for our family', ['staff' => 5], true, false);
});

test('a user with the dedicated moderator role (not super_admin) can access and use the moderation queue', function () {
    $moderatorUser = User::factory()->create()->assignRole('moderator');

    $indexResponse = $this->actingAs($moderatorUser)->get(route('admin.reviews.index'));
    $indexResponse->assertOk();
    $indexResponse->assertSee($this->review->title);

    $approveResponse = $this->actingAs($moderatorUser)->post(route('admin.reviews.approve', $this->review));
    $approveResponse->assertRedirect();
    expect($this->review->fresh()->status->value)->toBe('published');
});

test('super_admin can approve a pending review, which updates the agency review_score', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.reviews.approve', $this->review));

    $response->assertRedirect();
    expect($this->review->fresh()->status->value)->toBe('published');
    expect((float) $this->agency->fresh()->review_score)->toBe(5.0);

    Notification::assertSentTo($this->review->family->user, \App\Notifications\Review\ReviewApproved::class);
});

test('super_admin can reject a review with a reason', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.reviews.reject', $this->review), ['reason' => 'Contains identifying details']);

    $response->assertRedirect();
    expect($this->review->fresh()->status->value)->toBe('rejected');
});

test('super_admin can hide and then restore a published review', function () {
    $this->actingAs($this->admin)->post(route('admin.reviews.approve', $this->review));

    $hideResponse = $this->actingAs($this->admin)->post(route('admin.reviews.hide', $this->review), ['reason' => 'Under investigation']);
    $hideResponse->assertRedirect();
    expect($this->review->fresh()->status->value)->toBe('hidden');

    $restoreResponse = $this->actingAs($this->admin)->post(route('admin.reviews.restore', $this->review));
    $restoreResponse->assertRedirect();
    expect($this->review->fresh()->status->value)->toBe('published');
});

test('super_admin can feature and unfeature a review', function () {
    $this->actingAs($this->admin)->post(route('admin.reviews.approve', $this->review));

    $this->actingAs($this->admin)->post(route('admin.reviews.feature', $this->review));
    expect($this->review->fresh()->is_featured)->toBeTrue();

    $this->actingAs($this->admin)->post(route('admin.reviews.unfeature', $this->review));
    expect($this->review->fresh()->is_featured)->toBeFalse();
});

test('super_admin can delete a review, which soft-deletes it', function () {
    $response = $this->actingAs($this->admin)->delete(route('admin.reviews.destroy', $this->review));

    $response->assertRedirect();
    expect(Review::find($this->review->id))->toBeNull();
    expect(Review::withTrashed()->find($this->review->id))->not->toBeNull();
});

test('reported reviews appear in the moderation queue reported filter', function () {
    $reporter = User::factory()->create();
    app(ReviewReportService::class)->report($this->review, $reporter, 'spam', 'Looks fake');

    $response = $this->actingAs($this->admin)->get(route('admin.reviews.index', ['filter' => 'reported']));

    $response->assertOk();
    $response->assertSee($this->review->title);
});

test('super_admin can resolve a report', function () {
    $reporter = User::factory()->create();
    $report = app(ReviewReportService::class)->report($this->review, $reporter, 'spam');

    $response = $this->actingAs($this->admin)->post(route('admin.review-reports.resolve', $report), ['status' => 'dismissed']);

    $response->assertRedirect();
    expect($report->fresh()->status->value)->toBe('dismissed');
});

test('an advisor cannot access the review moderation queue at all', function () {
    $advisorUser = User::factory()->create()->assignRole('advisor');

    $this->actingAs($advisorUser)->get(route('admin.reviews.index'))->assertStatus(403);
});

test('a billing_manager (has invoices/commissions permissions but not reviews.moderate) cannot moderate reviews', function () {
    $billingUser = User::factory()->create()->assignRole('billing_manager');

    $this->actingAs($billingUser)->get(route('admin.reviews.index'))->assertStatus(403);
});
