<?php

use App\Models\Family;
use App\Models\User;

/**
 * Regression test for a real discoverability gap reported directly from
 * a screenshot: the "Recommended Agencies" link only existed on the
 * Care Seeker Edit page, one click deeper than the Care Seekers index
 * page a family actually lands on first. Also fixes the "Assessment
 * Done" badge, which previously counted in-progress assessments as done.
 */
beforeEach(function () {
    $this->withoutVite();
    $this->familyUser = User::factory()->create()->assignRole('family');
    $this->family = Family::create(['user_id' => $this->familyUser->id]);
});

test('Recommended Agencies link appears directly on the Care Seekers index page once the assessment is completed', function () {
    $careSeeker = $this->family->careSeekers()->create(['first_name' => 'Eleanor', 'last_name' => 'Test']);
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.index'));

    $response->assertOk();
    $response->assertSee('Recommended Agencies');
    $response->assertSee(route('family.care-seekers.recommendations.index', $careSeeker), false);
});

test('Recommended Agencies link does not appear before the assessment is completed', function () {
    $this->family->careSeekers()->create(['first_name' => 'NoAssessment', 'last_name' => 'Yet']);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.index'));

    $response->assertOk();
    $response->assertDontSee('Recommended Agencies');
});

test('Assessment Done badge only shows for a genuinely completed assessment, not an in-progress one', function () {
    $inProgressSeeker = $this->family->careSeekers()->create(['first_name' => 'InProgress', 'last_name' => 'Seeker']);
    $inProgressSeeker->needsAssessments()->create(['status' => 'in_progress', 'current_step' => 2]);

    $completedSeeker = $this->family->careSeekers()->create(['first_name' => 'Completed', 'last_name' => 'Seeker']);
    $completedSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.index'));
    $careSeekers = $response->viewData('careSeekers');

    expect($careSeekers->firstWhere('id', $inProgressSeeker->id)->completed_assessments_count)->toBe(0);
    expect($careSeekers->firstWhere('id', $completedSeeker->id)->completed_assessments_count)->toBeGreaterThan(0);
});
