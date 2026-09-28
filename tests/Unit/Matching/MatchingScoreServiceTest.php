<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Family;
use App\Services\Matching\MatchingScoreService;

beforeEach(function () {
    $this->withoutVite();
    $this->service = app(MatchingScoreService::class);
    $this->memoryCareCategory = AgencyCategory::where('code', 'memory_care')->first();
    $this->assistedLivingCategory = AgencyCategory::where('code', 'assisted_living')->first();
});

function makeMatchingCareSeeker(array $attrs = []): \App\Models\CareSeeker
{
    $family = Family::factory()->create();
    return $family->careSeekers()->create(array_merge([
        'first_name' => 'Test', 'last_name' => 'Seeker',
    ], $attrs));
}

test('exact care type match scores 100', function () {
    $careSeeker = makeMatchingCareSeeker(['care_type_needed' => 'memory_care']);
    $agency = Agency::factory()->create(['status' => 'published', 'agency_category_id' => $this->memoryCareCategory->id]);

    $result = $this->service->score($careSeeker, $agency);

    expect($result['factors']['care_type']['score'])->toBe(100);
});

test('mismatched care type scores low but not zero', function () {
    $careSeeker = makeMatchingCareSeeker(['care_type_needed' => 'memory_care']);
    $agency = Agency::factory()->create(['status' => 'published', 'agency_category_id' => $this->assistedLivingCategory->id]);

    $result = $this->service->score($careSeeker, $agency);

    expect($result['factors']['care_type']['score'])->toBeLessThan(50)->toBeGreaterThan(0);
});

test('care type factor is not applicable when no preference specified', function () {
    $careSeeker = makeMatchingCareSeeker(['care_type_needed' => null]);
    $agency = Agency::factory()->create(['status' => 'published', 'agency_category_id' => $this->memoryCareCategory->id]);

    $result = $this->service->score($careSeeker, $agency);

    expect($result['factors']['care_type']['applicable'])->toBeFalse();
});

test('budget within range scores 100', function () {
    $careSeeker = makeMatchingCareSeeker(['budget_min' => 3000, 'budget_max' => 5000]);
    $agency = Agency::factory()->create(['status' => 'published', 'min_monthly_cost' => 3500, 'max_monthly_cost' => 4500]);

    $result = $this->service->score($careSeeker, $agency);

    expect($result['factors']['budget']['score'])->toBe(100);
});

test('budget far outside range scores low', function () {
    $careSeeker = makeMatchingCareSeeker(['budget_min' => 2000, 'budget_max' => 3000]);
    $agency = Agency::factory()->create(['status' => 'published', 'min_monthly_cost' => 8000, 'max_monthly_cost' => 9000]);

    $result = $this->service->score($careSeeker, $agency);

    expect($result['factors']['budget']['score'])->toBeLessThan(20);
});

test('budget not applicable when neither side has data', function () {
    $careSeeker = makeMatchingCareSeeker(['budget_min' => null, 'budget_max' => null]);
    $agency = Agency::factory()->create(['status' => 'published', 'min_monthly_cost' => null, 'max_monthly_cost' => null]);

    $result = $this->service->score($careSeeker, $agency);

    expect($result['factors']['budget']['applicable'])->toBeFalse();
});

test('veteran benefits factor only applies to veterans', function () {
    $nonVeteran = makeMatchingCareSeeker(['is_veteran' => false]);
    $veteran = makeMatchingCareSeeker(['is_veteran' => true]);
    $agency = Agency::factory()->create(['status' => 'published', 'is_veteran_friendly' => true]);

    $nonVeteranResult = $this->service->score($nonVeteran, $agency);
    $veteranResult = $this->service->score($veteran, $agency);

    expect($nonVeteranResult['factors']['veteran_benefits']['applicable'])->toBeFalse();
    expect($veteranResult['factors']['veteran_benefits']['applicable'])->toBeTrue();
    expect($veteranResult['factors']['veteran_benefits']['score'])->toBe(100);
});

test('featured agencies receive the configured boost, non-featured do not', function () {
    $careSeeker = makeMatchingCareSeeker();
    $featured = Agency::factory()->create(['status' => 'published', 'is_featured' => true]);
    $notFeatured = Agency::factory()->create(['status' => 'published', 'is_featured' => false]);

    $featuredResult = $this->service->score($careSeeker, $featured);
    $notFeaturedResult = $this->service->score($careSeeker, $notFeatured);

    expect($featuredResult['featured_boost_applied'])->toBeGreaterThan(0);
    expect($notFeaturedResult['featured_boost_applied'])->toBe(0.0);
});

test('total score never exceeds 100 even with featured boost', function () {
    $careSeeker = makeMatchingCareSeeker([
        'care_type_needed' => 'memory_care', 'budget_min' => 3000, 'budget_max' => 10000,
    ]);
    $agency = Agency::factory()->create([
        'status' => 'published', 'agency_category_id' => $this->memoryCareCategory->id,
        'is_featured' => true, 'review_score' => 5.0, 'min_monthly_cost' => 3500, 'max_monthly_cost' => 4000,
        'has_availability' => true, 'is_wheelchair_accessible' => true, 'is_pet_friendly' => true,
    ]);

    $result = $this->service->score($careSeeker, $agency);

    expect($result['total_score'])->toBeLessThanOrEqual(100);
});

test('gender preference is not applicable when agency serves any gender', function () {
    $careSeeker = makeMatchingCareSeeker(['gender' => 'female']);
    $agency = Agency::factory()->create(['status' => 'published', 'gender_served' => 'any']);

    $result = $this->service->score($careSeeker, $agency);

    expect($result['factors']['gender_preference']['applicable'])->toBeFalse();
});

test('religious preference matches exactly scores 100', function () {
    $careSeeker = makeMatchingCareSeeker(['religious_preference' => 'Catholic']);
    $agency = Agency::factory()->create(['status' => 'published', 'religious_affiliation' => 'Catholic']);

    $result = $this->service->score($careSeeker, $agency);

    expect($result['factors']['religious_preference']['score'])->toBe(100);
});

test('score is deterministic - identical inputs produce identical scores', function () {
    $careSeeker = makeMatchingCareSeeker(['care_type_needed' => 'memory_care', 'budget_min' => 3000, 'budget_max' => 5000]);
    $agency = Agency::factory()->create(['status' => 'published', 'agency_category_id' => $this->memoryCareCategory->id]);

    $result1 = $this->service->score($careSeeker, $agency);
    $result2 = $this->service->score($careSeeker, $agency);

    expect($result1['total_score'])->toBe($result2['total_score']);
});
