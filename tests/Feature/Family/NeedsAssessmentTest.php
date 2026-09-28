<?php

use App\Models\Family;
use App\Models\User;
use App\Services\Family\NeedsAssessmentService;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('family');
    $this->family = Family::create(['user_id' => $this->user->id]);
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);
});

test('family can start a needs assessment for a care seeker', function () {
    $response = $this->actingAs($this->user)->get(route('family.needs-assessment.start', $this->careSeeker));

    $response->assertRedirect(route('family.needs-assessment.step', [$this->careSeeker, 1]));
    expect($this->careSeeker->needsAssessments()->where('status', 'in_progress')->exists())->toBeTrue();
});

test('starting twice resumes the same in-progress assessment rather than creating a second one', function () {
    $this->actingAs($this->user)->get(route('family.needs-assessment.start', $this->careSeeker));
    $this->actingAs($this->user)->get(route('family.needs-assessment.start', $this->careSeeker));

    expect($this->careSeeker->needsAssessments()->count())->toBe(1);
});

test('step 1 renders active questions for the care_type and timeline sections', function () {
    $response = $this->actingAs($this->user)->get(route('family.needs-assessment.step', [$this->careSeeker, 1]));

    $response->assertOk();
    $response->assertSee('What type of care are you looking for?');
    $response->assertSee('When are you looking to move?');
});

test('submitting step 1 saves answers and advances current_step', function () {
    $response = $this->actingAs($this->user)->post(
        route('family.needs-assessment.step.store', [$this->careSeeker, 1]),
        ['answers' => ['care_type_needed' => 'assisted_living', 'move_in_timeline' => 'immediately']]
    );

    $response->assertRedirect(route('family.needs-assessment.step', [$this->careSeeker, 2]));

    $assessment = $this->careSeeker->needsAssessments()->first();
    expect($assessment->current_step)->toBe(2);
    expect($assessment->answers()->count())->toBe(2);
});

test('an invalid single_select answer value is rejected', function () {
    $response = $this->actingAs($this->user)->post(
        route('family.needs-assessment.step.store', [$this->careSeeker, 1]),
        ['answers' => ['care_type_needed' => 'not_a_real_option']]
    );

    $response->assertSessionHasErrors();
});

test('multi_select answers accept arrays', function () {
    $response = $this->actingAs($this->user)->post(
        route('family.needs-assessment.step.store', [$this->careSeeker, 4]),
        ['answers' => ['adl_needs' => ['bathing', 'dressing']]]
    );

    $response->assertRedirect();
    $assessment = $this->careSeeker->needsAssessments()->first();
    $answer = $assessment->answers()->whereHas('question', fn ($q) => $q->where('code', 'adl_needs'))->first();
    expect($answer->answer_value)->toBe(['bathing', 'dressing']);
});

test('conditional follow-up question is only in scope for its section but always in the question bank', function () {
    // dementia_diagnosis has a display_condition on memory_status — it
    // still belongs to step 3's question set (server always returns it;
    // the conditional show/hide is a client-side Alpine concern), so
    // submitting it should be accepted regardless of the sibling value
    // sent in the same request.
    $response = $this->actingAs($this->user)->post(
        route('family.needs-assessment.step.store', [$this->careSeeker, 3]),
        ['answers' => ['memory_status' => 'moderate', 'dementia_diagnosis' => 'yes']]
    );

    $response->assertRedirect();
    $assessment = $this->careSeeker->needsAssessments()->first();
    expect($assessment->answers()->count())->toBe(2);
});

test('completing the assessment writes answers back to the care seeker profile', function () {
    $this->actingAs($this->user)->post(route('family.needs-assessment.step.store', [$this->careSeeker, 1]), [
        'answers' => ['care_type_needed' => 'memory_care'],
    ]);
    $this->actingAs($this->user)->post(route('family.needs-assessment.step.store', [$this->careSeeker, 3]), [
        'answers' => ['mobility' => 'wheelchair'],
    ]);

    $response = $this->actingAs($this->user)->post(route('family.needs-assessment.complete', $this->careSeeker));

    $response->assertRedirect(route('family.care-seekers.edit', $this->careSeeker));

    $this->careSeeker->refresh();
    expect($this->careSeeker->care_type_needed->value)->toBe('memory_care');
    expect($this->careSeeker->mobility->value)->toBe('wheelchair');

    $assessment = $this->careSeeker->needsAssessments()->first();
    expect($assessment->status)->toBe('completed');
    expect($assessment->completed_at)->not->toBeNull();
    expect($assessment->score_profile)->toBeArray();
});

test('drafts page lists in-progress assessments and completed assessments separately', function () {
    $this->actingAs($this->user)->get(route('family.needs-assessment.start', $this->careSeeker));

    $response = $this->actingAs($this->user)->get(route('family.needs-assessment.drafts'));

    $response->assertOk();
    $response->assertSee($this->careSeeker->full_name);
});

test('family cannot access another familys care seeker assessment', function () {
    $otherFamily = Family::factory()->create();
    $otherCareSeeker = $otherFamily->careSeekers()->create(['first_name' => 'Not', 'last_name' => 'Yours']);

    $response = $this->actingAs($this->user)->get(route('family.needs-assessment.start', $otherCareSeeker));

    $response->assertStatus(403);
});

test('requesting an out of range step returns 404', function () {
    $response = $this->actingAs($this->user)->get(route('family.needs-assessment.step', [$this->careSeeker, 99]));

    $response->assertStatus(404);
});

test('step 6 shows the review summary of all recorded answers', function () {
    $this->actingAs($this->user)->post(route('family.needs-assessment.step.store', [$this->careSeeker, 1]), [
        'answers' => ['care_type_needed' => 'assisted_living'],
    ]);

    $response = $this->actingAs($this->user)->get(route('family.needs-assessment.step', [$this->careSeeker, 6]));

    $response->assertOk();
    $response->assertSee('Review');
});
