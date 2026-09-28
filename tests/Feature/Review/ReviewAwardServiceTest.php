<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\Referral;
use App\Models\User;
use App\Services\Review\ReviewAwardService;
use App\Services\Review\ReviewModerationService;
use App\Services\Review\ReviewSubmissionService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
    $this->advisorUser = User::factory()->create()->assignRole('advisor');
    $this->advisor = Advisor::create(['user_id' => $this->advisorUser->id, 'is_active' => true]);
});

function createApprovedReviewForAward(Advisor $advisor, Agency $agency, array $categoryRatings, ?bool $recommend = true): void
{
    $family = Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $lead = $family->leads()->create(['care_seeker_id' => $careSeeker->id, 'advisor_id' => $advisor->id, 'status' => 'assigned', 'source' => 'manual']);
    $referral = Referral::create(['family_id' => $family->id, 'care_seeker_id' => $careSeeker->id, 'agency_id' => $agency->id, 'advisor_id' => $advisor->id, 'lead_id' => $lead->id, 'status' => 'converted']);
    $review = app(ReviewSubmissionService::class)->submit($family, $referral, 'Great', 'Wonderful experience overall for our family', $categoryRatings, $recommend, false);
    app(ReviewModerationService::class)->approve($review);
}

test('an agency needs at least 3 published reviews to be eligible for any award', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    createApprovedReviewForAward($this->advisor, $agency, ['staff' => 5]);
    createApprovedReviewForAward($this->advisor, $agency, ['staff' => 5]);

    $awards = app(ReviewAwardService::class)->currentAwards();

    expect($awards['top_rated']?->id)->not->toBe($agency->id);
});

test('the agency with the highest average rating wins Top Rated once it has 3+ reviews', function () {
    $highRated = Agency::factory()->create(['status' => 'published']);
    $lowRated = Agency::factory()->create(['status' => 'published']);

    for ($i = 0; $i < 3; $i++) {
        createApprovedReviewForAward($this->advisor, $highRated, ['staff' => 5]);
        createApprovedReviewForAward($this->advisor, $lowRated, ['staff' => 2]);
    }

    $awards = app(ReviewAwardService::class)->currentAwards();

    expect($awards['top_rated']->id)->toBe($highRated->id);
});

test('Best Staff award goes to the agency with the highest average staff category rating specifically', function () {
    $bestStaff = Agency::factory()->create(['status' => 'published']);
    $worseStaff = Agency::factory()->create(['status' => 'published']);

    for ($i = 0; $i < 3; $i++) {
        createApprovedReviewForAward($this->advisor, $bestStaff, ['staff' => 5, 'value' => 2]);
        createApprovedReviewForAward($this->advisor, $worseStaff, ['staff' => 2, 'value' => 5]);
    }

    $awards = app(ReviewAwardService::class)->currentAwards();

    expect($awards['best_staff']->id)->toBe($bestStaff->id);
});

test('Family Favorite reflects would_recommend rate, distinct from raw star rating', function () {
    $recommended = Agency::factory()->create(['status' => 'published']);
    $notRecommended = Agency::factory()->create(['status' => 'published']);

    for ($i = 0; $i < 3; $i++) {
        createApprovedReviewForAward($this->advisor, $recommended, ['staff' => 3], true);
        createApprovedReviewForAward($this->advisor, $notRecommended, ['staff' => 5], false);
    }

    $awards = app(ReviewAwardService::class)->currentAwards();

    expect($awards['family_favorite']->id)->toBe($recommended->id);
    expect($awards['top_rated']->id)->toBe($notRecommended->id);
});

test('an individual agency can see which specific awards it has won', function () {
    $agency = Agency::factory()->create(['status' => 'published']);
    for ($i = 0; $i < 3; $i++) {
        createApprovedReviewForAward($this->advisor, $agency, ['staff' => 5, 'value' => 5]);
    }

    $awards = app(ReviewAwardService::class)->awardsFor($agency);

    expect($awards)->toContain('top_rated');
    expect($awards)->toContain('most_reviewed');
});
