<?php

use App\Models\Agency;
use App\Models\Family;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->familyUser = User::factory()->create()->assignRole('family');
    $this->family = Family::create(['user_id' => $this->familyUser->id]);
    $this->careSeeker = $this->family->careSeekers()->create(['first_name' => 'Test', 'last_name' => 'Seeker']);
    $this->careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
});

/**
 * Regression test for a real reported bug: clicking "Save Agency" on the
 * Recommendations page returned "The agencies field is required." Root
 * cause was an illegally nested <form> — the entire results grid was
 * wrapped in <form id="compareForm"> for the Compare feature, and each
 * card's own Save/Shortlist <form> was nested inside it. Nested forms are
 * invalid HTML with undefined browser behavior; clicking Save actually
 * submitted the outer Compare form instead. Fixed by removing the
 * wrapping form entirely (Compare selection is now plain checkboxes +
 * JS navigation, no form at all).
 */
test('the recommendations page renders no nested form elements', function () {
    Agency::factory()->count(3)->create(['status' => 'published']);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', $this->careSeeker));
    $response->assertOk();

    $html = $response->getContent();

    // Walk every <form ...> opening tag position and verify no other
    // <form opens before the next matching </form> closes — i.e. no
    // nesting anywhere on the page.
    preg_match_all('/<form\b|<\/form>/i', $html, $matches);
    $depth = 0;
    $maxDepth = 0;
    foreach ($matches[0] as $tag) {
        if (stripos($tag, '</form>') === 0) {
            $depth--;
        } else {
            $depth++;
            $maxDepth = max($maxDepth, $depth);
        }
    }

    expect($maxDepth)->toBeLessThanOrEqual(1);
});

test('Save Agency (favorite toggle) succeeds from the recommendations page context without any validation error', function () {
    $agency = Agency::factory()->create(['status' => 'published']);

    $response = $this->actingAs($this->familyUser)
        ->from(route('family.care-seekers.recommendations.index', $this->careSeeker))
        ->post(route('family.favorites.toggle', $agency));

    $response->assertRedirect(route('family.care-seekers.recommendations.index', $this->careSeeker));
    $response->assertSessionDoesntHaveErrors();
    expect($this->family->favorites()->where('agency_id', $agency->id)->exists())->toBeTrue();
});

test('the compare bar and its selection checkboxes are not inside any form element', function () {
    Agency::factory()->count(3)->create(['status' => 'published']);

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', $this->careSeeker));

    $response->assertOk();
    $response->assertDontSee('id="compareForm"', false);
    $response->assertSee('id="compareBar"', false);
    $response->assertSee('compareBarButton', false);
});

test('BUGFIX: the Distance filter on the Recommendations page actually excludes far-away agencies now', function () {
    $this->careSeeker->update(['preferred_city' => 'Austin', 'preferred_state' => 'TX']);
    $near = Agency::factory()->create(['status' => 'published', 'city' => 'Austin', 'state' => 'TX']);
    $far = Agency::factory()->create(['status' => 'published', 'city' => 'Seattle', 'state' => 'WA']);

    // Without a distance filter, both should be candidates (far one still
    // shows since it's within the default 100mi... no wait, Seattle is
    // NOT within 100mi of Austin, so it would already be hard-excluded
    // at generation time. Use a max_distance filter tighter than what a
    // same-state-but-different-city agency would need instead.
    $sameState = Agency::factory()->create(['status' => 'published', 'city' => 'Dallas', 'state' => 'TX']); // ~182mi from Austin

    // Raise the hard max-distance so both Austin and Dallas are valid
    // candidates at all, then use the page's own filter to narrow further.
    app(\App\Services\Settings\SettingsService::class)->set('matching_max_distance_miles', '300', 'matching', 'int');

    $response = $this->actingAs($this->familyUser)->get(route('family.care-seekers.recommendations.index', [
        'care_seeker' => $this->careSeeker, 'max_distance' => 25, 'show_all' => 1,
    ]));

    $response->assertOk();
    $results = $response->viewData('results');

    expect($results->pluck('agency_id'))->toContain($near->id);
    expect($results->pluck('agency_id'))->not->toContain($sameState->id);
});
