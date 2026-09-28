<?php

use App\Models\User;
use App\Services\Settings\SettingsService;
use Database\Seeders\AdminUserSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('super_admin can view the matching weights settings page', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.matching.edit'));

    $response->assertOk();
});

test('super_admin can update matching weights', function () {
    $newWeights = [
        'care_type' => 20, 'location' => 10, 'coverage_area' => 5, 'distance' => 10, 'budget' => 15,
        'services_offered' => 5, 'languages' => 5, 'insurance_accepted' => 5, 'medicaid_medicare' => 5,
        'specialty_care' => 3, 'memory_care' => 8, 'mobility' => 5, 'availability' => 4,
        'gender_preference' => 2, 'veteran_benefits' => 3, 'religious_preference' => 2,
        'pet_friendly' => 2, 'accessibility' => 3, 'review_rating' => 7, 'agency_quality' => 3, 'verification_status' => 2,
    ];

    $response = $this->actingAs($this->admin)->put(route('admin.settings.matching.update'), [
        'weights' => $newWeights, 'featured_boost' => 5, 'max_distance_miles' => 75, 'min_score_threshold' => 40,
    ]);

    $response->assertRedirect();
    $settings = app(SettingsService::class);
    expect($settings->get('matching_weights')['care_type'])->toBe(20);
    expect((int) $settings->get('matching_featured_boost'))->toBe(5);
    expect((int) $settings->get('matching_max_distance_miles'))->toBe(75);
});

test('weight update validation rejects a missing factor', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.settings.matching.update'), [
        'weights' => ['care_type' => 20], // missing all other required factors
        'featured_boost' => 3, 'max_distance_miles' => 100, 'min_score_threshold' => 30,
    ]);

    $response->assertSessionHasErrors();
});

test('non-admin roles cannot access matching weight settings', function () {
    $advisor = User::factory()->create()->assignRole('advisor');
    $family = User::factory()->create()->assignRole('family');
    $agencyOwner = User::factory()->create()->assignRole('agency_owner');

    foreach ([$advisor, $family, $agencyOwner] as $user) {
        $this->actingAs($user)->get(route('admin.settings.matching.edit'))->assertStatus(403);
    }
});

test('changing weights via settings actually changes computed scores', function () {
    $family = \App\Models\Family::factory()->create();
    $careSeeker = $family->careSeekers()->create(['first_name' => 'Weight', 'last_name' => 'Test', 'is_veteran' => true]);
    $agency = \App\Models\Agency::factory()->create(['status' => 'published', 'is_veteran_friendly' => true]);

    $settings = app(SettingsService::class);
    $weights = $settings->get('matching_weights');

    $weights['veteran_benefits'] = 1;
    $settings->set('matching_weights', $weights, 'matching', 'json');
    $scoreWithLowWeight = app(\App\Services\Matching\MatchingScoreService::class)->score($careSeeker, $agency)['total_score'];

    $weights['veteran_benefits'] = 50;
    $settings->set('matching_weights', $weights, 'matching', 'json');
    $scoreWithHighWeight = app(\App\Services\Matching\MatchingScoreService::class)->score($careSeeker, $agency)['total_score'];

    expect($scoreWithHighWeight)->not->toBe($scoreWithLowWeight);
});
