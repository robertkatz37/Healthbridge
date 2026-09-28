<?php

use App\Models\Agency;
use App\Models\Family;
use App\Models\MatchResult;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;

beforeEach(function () {
    $this->withoutVite();
    $this->familyUser = User::factory()->create()->assignRole('family');
    $this->family = Family::create(['user_id' => $this->familyUser->id]);
});

test('family can view their dedicated shortlist page showing agencies across all care seekers', function () {
    $seeker1 = $this->family->careSeekers()->create(['first_name' => 'One', 'last_name' => 'Seeker']);
    $seeker1->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $agency1 = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($seeker1);
    MatchResult::where('agency_id', $agency1->id)->update(['is_family_shortlisted' => true]);

    $seeker2 = $this->family->careSeekers()->create(['first_name' => 'Two', 'last_name' => 'Seeker']);
    $seeker2->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    $agency2 = Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($seeker2);
    MatchResult::where('agency_id', $agency2->id)->where('needs_assessment_id', $seeker2->latestCompletedAssessment()->id)->update(['is_family_shortlisted' => true]);

    $response = $this->actingAs($this->familyUser)->get(route('family.shortlist.index'));

    $response->assertOk();
    $shortlisted = $response->viewData('shortlisted');
    expect($shortlisted->pluck('agency_id'))->toContain($agency1->id);
    expect($shortlisted->pluck('agency_id'))->toContain($agency2->id);
});

test('shortlist page never shows another familys shortlisted agencies', function () {
    $otherFamily = Family::factory()->create();
    $otherSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Other', 'last_name' => 'Family']);
    $otherSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    Agency::factory()->create(['status' => 'published']);
    app(MatchingEngineService::class)->generateRecommendations($otherSeeker);
    MatchResult::query()->update(['is_family_shortlisted' => true]);

    $response = $this->actingAs($this->familyUser)->get(route('family.shortlist.index'));

    $shortlisted = $response->viewData('shortlisted');
    expect($shortlisted)->toBeEmpty();
});
