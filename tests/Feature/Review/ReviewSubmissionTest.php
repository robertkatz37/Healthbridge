<?php

use App\Models\Advisor;
use App\Models\Agency;
use App\Models\Family;
use App\Models\Referral;
use App\Models\Review;
use App\Models\User;
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
});

test('family can view their My Reviews page listing pending referrals and submitted reviews', function () {
    Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'move_in_confirmed']);

    $response = $this->actingAs($this->familyUser)->get(route('family.reviews.index'));

    $response->assertOk();
    $response->assertSee($this->agency->name);
});

test('family can view the review submission form for an eligible referral', function () {
    $referral = Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'converted']);

    $response = $this->actingAs($this->familyUser)->get(route('family.reviews.create', $referral));

    $response->assertOk();
    $response->assertSee('Staff');
    $response->assertSee('Cleanliness');
});

test('family cannot view the review form for a referral that has not reached move-in', function () {
    $referral = Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'tour_scheduled']);

    $response = $this->actingAs($this->familyUser)->get(route('family.reviews.create', $referral));

    $response->assertRedirect();
    $response->assertSessionHasErrors();
});

test('family can submit a complete review with category ratings', function () {
    User::factory()->create()->assignRole('super_admin');
    $referral = Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'converted']);

    $response = $this->actingAs($this->familyUser)->post(route('family.reviews.store', $referral), [
        'title' => 'Wonderful experience',
        'body' => 'The staff were caring and attentive throughout the whole process.',
        'category_ratings' => ['staff' => 5, 'cleanliness' => 4, 'care_quality' => 5],
        'would_recommend' => true,
        'is_anonymous' => false,
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();

    $review = Review::where('referral_id', $referral->id)->first();
    expect($review)->not->toBeNull();
    expect($review->status->value)->toBe('pending_moderation');
    expect((float) $review->overall_rating)->toBe(4.67);
    expect($review->categoryRatings()->count())->toBe(3);

    Notification::assertSentTimes(\App\Notifications\Review\ReviewSubmitted::class, 1);
});

test('submitting a review a second time for the same referral fails validation', function () {
    $referral = Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'converted']);

    $this->actingAs($this->familyUser)->post(route('family.reviews.store', $referral), [
        'title' => 'First review', 'body' => 'A perfectly reasonable length review body here.',
        'category_ratings' => ['staff' => 5],
    ]);

    $response = $this->actingAs($this->familyUser)->post(route('family.reviews.store', $referral), [
        'title' => 'Second attempt', 'body' => 'Trying to submit another one for the same referral.',
        'category_ratings' => ['staff' => 3],
    ]);

    $response->assertSessionHasErrors();
    expect(Review::where('referral_id', $referral->id)->count())->toBe(1);
});

test('a family cannot submit a review for another familys referral', function () {
    $otherFamily = Family::factory()->create();
    $otherFamilyUser = $otherFamily->user;
    $otherFamilyUser->assignRole('family');
    $referral = Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'converted']);

    $response = $this->actingAs($otherFamilyUser)->post(route('family.reviews.store', $referral), [
        'title' => 'Sneaky review', 'body' => 'Trying to review a referral that is not mine.',
        'category_ratings' => ['staff' => 1],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors();
    expect(Review::where('referral_id', $referral->id)->count())->toBe(0);
});

test('agency_owner cannot submit a review', function () {
    $agencyOwnerUser = User::factory()->create()->assignRole('agency_owner');
    $referral = Referral::create(['family_id' => $this->family->id, 'care_seeker_id' => $this->careSeeker->id, 'agency_id' => $this->agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $this->lead->id, 'status' => 'converted']);

    $response = $this->actingAs($agencyOwnerUser)->get(route('family.reviews.create', $referral));

    $response->assertStatus(403);
});

test('REGRESSION: a family whose relationship_to_seeker was never set (as with every real registration) can still submit a review without a 500 error', function () {
    $rawFamilyUser = User::factory()->create()->assignRole('family');
    $rawFamily = Family::create(['user_id' => $rawFamilyUser->id]);
    $rawCareSeeker = $rawFamily->careSeekers()->create(['first_name' => 'Raw', 'last_name' => 'Family']);
    $rawLead = $rawFamily->leads()->create(['care_seeker_id' => $rawCareSeeker->id, 'advisor_id' => $this->advisor->id, 'status' => 'assigned', 'source' => 'manual']);
    $agency = Agency::factory()->create(['status' => 'published']);
    $referral = Referral::create(['family_id' => $rawFamily->id, 'care_seeker_id' => $rawCareSeeker->id, 'agency_id' => $agency->id, 'advisor_id' => $this->advisor->id, 'lead_id' => $rawLead->id, 'status' => 'converted']);

    $response = $this->actingAs($rawFamilyUser)->post(route('family.reviews.store', $referral), [
        'title' => 'No profile filled in yet', 'body' => 'Submitting without ever having set a relationship.',
        'category_ratings' => ['staff' => 4],
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    $review = Review::where('referral_id', $referral->id)->first();
    expect($review)->not->toBeNull();
    expect($review->reviewer_relationship)->toBe('family_member');
});
