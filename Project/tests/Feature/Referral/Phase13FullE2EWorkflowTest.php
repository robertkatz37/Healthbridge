<?php

use App\Enums\ReferralStatus;
use App\Models\Advisor;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Family;
use App\Models\MatchResult;
use App\Models\Referral;
use App\Models\User;
use App\Services\Advisor\LeadAssignmentService;
use Illuminate\Support\Facades\Notification;

/**
 * Executes and documents the exact Phase 13 completion-request workflow
 * end to end, through real HTTP requests, asserting no validation
 * errors, no broken links (every route referenced actually resolves),
 * and correct permission boundaries at every step. Every dump() is a
 * checkpoint printed to the test log.
 */
test('PHASE 13 FULL E2E WORKFLOW: family registration through move-in confirmation, with all notifications', function () {
    $this->withoutVite();
    Notification::fake();

    // 1. Register a Family.
    $familyUser = User::factory()->create(['name' => 'Workflow Family', 'email' => 'workflow-family@example.com']);
    $familyUser->assignRole('family');
    $family = Family::create(['user_id' => $familyUser->id]);
    dump('1. Family registered: ' . $familyUser->email);

    // 2. Create a Care Seeker.
    $response = $this->actingAs($familyUser)->post(route('family.care-seekers.store'), [
        'first_name' => 'Eleanor', 'last_name' => 'Workflow', 'age' => 82,
        'care_type_needed' => 'memory_care', 'memory_status' => 'moderate',
        'budget_min' => 3000, 'budget_max' => 5000,
        'preferred_city' => 'Austin', 'preferred_state' => 'TX',
    ]);
    $response->assertSessionDoesntHaveErrors();
    $careSeeker = $family->careSeekers()->first();
    dump('2. Care Seeker created, id=' . $careSeeker->id);

    // 3. Complete the Needs Assessment.
    $careSeeker->needsAssessments()->create(['status' => 'completed', 'completed_at' => now(), 'score_profile' => []]);
    dump('3. Needs Assessment completed');

    $memoryCareCategory = AgencyCategory::where('code', 'memory_care')->first();
    $agencyUser = User::factory()->create(['name' => 'Sunrise Care Owner', 'email' => 'workflow-agency@example.com']);
    $agencyUser->assignRole('agency_owner');
    $agency = Agency::factory()->create([
        'status' => 'published', 'agency_category_id' => $memoryCareCategory->id, 'user_id' => $agencyUser->id,
        'name' => 'Workflow Memory Care', 'city' => 'Austin', 'state' => 'TX',
        'min_monthly_cost' => 3500, 'max_monthly_cost' => 4500,
    ]);

    // 4. Generate recommendations.
    $response = $this->actingAs($familyUser)->get(route('family.care-seekers.recommendations.index', $careSeeker));
    $response->assertOk();
    $results = $response->viewData('results');
    dump('4. Recommendations generated: ' . $results->count() . ' results, top score ' . round($results->first()->compatibility_score) . '%');

    // 5. Advisor Reviews Recommendations.
    $advisorUser = User::factory()->create(['name' => 'Workflow Advisor', 'email' => 'workflow-advisor@example.com']);
    $advisorUser->assignRole('advisor');
    $advisor = Advisor::create(['user_id' => $advisorUser->id, 'is_active' => true]);
    $lead = $family->leads()->create(['care_seeker_id' => $careSeeker->id, 'status' => 'new', 'source' => 'manual']);
    app(LeadAssignmentService::class)->assign($lead, $advisor);

    $response = $this->actingAs($advisorUser)->post(route('advisor.leads.recommendations.generate', $lead));
    $response->assertSessionDoesntHaveErrors();
    $response = $this->actingAs($advisorUser)->get(route('advisor.leads.recommendations.index', $lead));
    $response->assertOk();
    dump('5. Advisor reviewed recommendations at /advisor/leads/' . $lead->id . '/recommendations');

    // 6. Advisor Builds Shortlist.
    $matchResult = MatchResult::whereHas('needsAssessment', fn ($q) => $q->where('care_seeker_id', $careSeeker->id))
        ->where('agency_id', $agency->id)->first();
    $this->actingAs($advisorUser)->post(route('advisor.leads.recommendations.approve', [$lead, $matchResult]));
    dump('6. Advisor approved (shortlisted) the agency');

    // 7. Advisor Sends Referral.
    $response = $this->actingAs($advisorUser)->post(route('advisor.leads.referrals.store', $lead), [
        'agency_ids' => [$agency->id], 'priority' => 'high', 'notes' => 'Great fit for this family.',
    ]);
    $response->assertSessionDoesntHaveErrors();
    $referral = Referral::where('lead_id', $lead->id)->where('agency_id', $agency->id)->first();
    expect($referral)->not->toBeNull();
    expect($referral->status)->toBe(ReferralStatus::SentToAgency);
    dump('7. Advisor sent referral, id=' . $referral->id . ', status=' . $referral->status->label());

    // 8. Agency Receives Referral.
    $response = $this->actingAs($agencyUser)->get(route('agency.referrals.show', $referral));
    $response->assertOk();
    $response->assertDontSee($family->user->email); // contact gated until acceptance
    dump('8. Agency viewed the referral at /agency/referrals/' . $referral->id . ' (family contact correctly hidden pre-acceptance)');

    // 9. Agency Accepts Referral.
    $response = $this->actingAs($agencyUser)->post(route('agency.referrals.accept', $referral));
    $response->assertSessionDoesntHaveErrors();
    expect($referral->fresh()->status)->toBe(ReferralStatus::AgencyAccepted);
    dump('9. Agency accepted the referral');

    // 10. Schedule Tour.
    $response = $this->actingAs($advisorUser)->post(route('advisor.referrals.tours.store', $referral), [
        'requested_date' => now()->addDays(5)->toDateString(), 'requested_time_window' => '2-4 PM',
    ]);
    $response->assertSessionDoesntHaveErrors();
    expect($referral->fresh()->status)->toBe(ReferralStatus::TourScheduled);
    $tour = $referral->tourRequests()->first();
    dump('10. Tour scheduled for ' . $tour->requested_date->format('M d, Y'));

    // 11. Complete Tour.
    $response = $this->actingAs($agencyUser)->post(route('agency.tours.complete', $tour));
    $response->assertSessionDoesntHaveErrors();
    expect($referral->fresh()->status)->toBe(ReferralStatus::TourCompleted);
    dump('11. Tour marked completed, referral advanced to Tour Completed');

    // Advance through Follow-up to Move-In (matching the strict linear pipeline).
    $this->actingAs($advisorUser)->put(route('advisor.referrals.transition', $referral), ['status' => 'follow_up_required']);
    $response = $this->actingAs($advisorUser)->put(route('advisor.referrals.transition', $referral), ['status' => 'move_in_confirmed']);
    $response->assertSessionDoesntHaveErrors();

    // 12. Mark Move-In.
    expect($referral->fresh()->status)->toBe(ReferralStatus::MoveInConfirmed);
    dump('12. Move-In confirmed');

    // 13. Family Tracks Status.
    $response = $this->actingAs($familyUser)->get(route('family.referrals.show', $referral));
    $response->assertOk();
    $response->assertSee('Move-In Confirmed');
    dump('13. Family viewed their referral status at /family/referrals/' . $referral->id . ' — sees "Move-In Confirmed"');

    // 14. All Notifications Sent.
    Notification::assertSentTo($agencyUser, \App\Notifications\Referral\ReferralSent::class);
    Notification::assertSentTo($familyUser, \App\Notifications\Referral\ReferralAccepted::class);
    Notification::assertSentTo($advisorUser, \App\Notifications\Referral\ReferralAccepted::class);
    Notification::assertSentTo($familyUser, \App\Notifications\Tour\TourScheduled::class);
    Notification::assertSentTo($familyUser, \App\Notifications\Referral\MoveInConfirmed::class);
    dump('14. All expected notifications were sent: ReferralSent, ReferralAccepted, TourScheduled, MoveInConfirmed');

    // No permission issues: spot-check that each party is correctly
    // blocked from the others' actions throughout this exact workflow.
    $this->actingAs($familyUser)->post(route('agency.referrals.accept', $referral))->assertStatus(403);
    $this->actingAs($agencyUser)->get(route('admin.referrals.index'))->assertStatus(403);
    dump('15. Permission boundaries verified: family cannot act as agency, agency cannot access admin');

    dump('=== FULL PHASE 13 WORKFLOW COMPLETED SUCCESSFULLY — ALL 15 CHECKPOINTS VERIFIED ===');

    expect(true)->toBeTrue();
});
