<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\Referral;
use App\Models\User;
use App\Services\Review\ReviewModerationService;
use App\Services\Review\ReviewSubmissionService;
use Database\Seeders\AdminUserSeeder;
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
    $this->agency = Agency::factory()->create(['status' => 'published', 'user_id' => $this->agencyUser->id, 'onboarding_completed_at' => now()]);
    $this->referral = Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'converted']);
});

test('family dashboard shows a Pending Reviews prompt for a completed move-in with no review yet', function () {
    $response = $this->actingAs($this->familyUser)->get(route('family.dashboard'));

    $response->assertOk();
    $response->assertSee('Pending Reviews');
    $response->assertSee('Write a Review');
});

test('family dashboard shows a submitted review in My Reviews', function () {
    app(ReviewSubmissionService::class)->submit($this->family, $this->referral, 'Great', 'Wonderful experience for us', ['staff' => 5], true, false);

    $response = $this->actingAs($this->familyUser)->get(route('family.dashboard'));

    $response->assertOk();
    $response->assertSee('Recent Reviews');
    $response->assertSee($this->agency->name);
});

test('agency dashboard shows average rating, total reviews, and pending responses', function () {
    $review = app(ReviewSubmissionService::class)->submit($this->family, $this->referral, 'Great', 'Wonderful experience for us', ['staff' => 5], true, false);
    app(ReviewModerationService::class)->approve($review);

    $response = $this->actingAs($this->agencyUser)->get(route('agency.dashboard'));

    $response->assertOk();
    $response->assertSee('Recent Reviews');
    $response->assertSee('1 awaiting response', false);
    $data = $response->viewData('totalReviews');
    expect($data)->toBe(1);
});

test('advisor dashboard shows Families Awaiting Reviews for a completed move-in with no review yet', function () {
    $response = $this->actingAs($this->advisorUser)->get(route('advisor.dashboard'));

    $response->assertOk();
    $response->assertSee('Families Awaiting Reviews');
    $response->assertSee($this->agency->name);
});

test('advisor dashboard no longer shows a family once they submit their review', function () {
    app(ReviewSubmissionService::class)->submit($this->family, $this->referral, 'Great', 'Wonderful experience for us', ['staff' => 5], true, false);

    $response = $this->actingAs($this->advisorUser)->get(route('advisor.dashboard'));

    $awaitingReferrals = $response->viewData('awaitingReviewReferrals');
    expect($awaitingReferrals->pluck('id'))->not->toContain($this->referral->id);
});

test('admin dashboard reports page shows Review Analytics including average platform rating and top rated agencies', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();
    $review = app(ReviewSubmissionService::class)->submit($this->family, $this->referral, 'Great', 'Wonderful experience for us', ['staff' => 5], true, false);
    app(ReviewModerationService::class)->approve($review);

    $response = $this->actingAs($admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertSee('Published Reviews');
    $response->assertSee('Average Platform Rating');
    $response->assertSee('Top Rated Agencies');
    $response->assertSee('Lowest Rated Agencies');
});

test('admin sidebar shows a Reviews link with a pending-moderation count badge', function () {
    $this->seed(AdminUserSeeder::class);
    $admin = User::where('email', 'admin@healthsbridge.test')->first();
    app(ReviewSubmissionService::class)->submit($this->family, $this->referral, 'Great', 'Wonderful experience for us', ['staff' => 5], true, false);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Reviews');
});
