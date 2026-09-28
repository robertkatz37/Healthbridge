<?php

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\Family;
use App\Models\Referral;
use App\Models\User;
use App\Services\Agency\AgencyProvisioningService;

beforeEach(function () {
    $this->withoutVite();
    $this->owner = User::factory()->create();
    $this->owner->assignRole('agency_owner');
    $this->category = AgencyCategory::first();

    $this->agency = app(AgencyProvisioningService::class)->createDraftAgency($this->owner, [
        'agency_category_id' => $this->category->id,
        'name' => 'Test Agency',
    ]);
    $this->agency->update(['status' => 'published', 'onboarding_completed_at' => now()]);
});

// ─── Lead Inbox ─────────────────────────────────────────────────────────────

test('agency owner can view their lead inbox', function () {
    $response = $this->actingAs($this->owner)->get(route('agency.leads.index'));

    $response->assertOk();
});

test('lead inbox shows only referrals for this agency', function () {
    $family = Family::factory()->create();
    Referral::factory()->create(['agency_id' => $this->agency->id, 'family_id' => $family->id]);

    $otherAgency = Agency::factory()->create();
    Referral::factory()->create(['agency_id' => $otherAgency->id, 'family_id' => $family->id]);

    $response = $this->actingAs($this->owner)->get(route('agency.leads.index'));

    $response->assertOk();
    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
});

test('lead inbox can be filtered by status', function () {
    $family = Family::factory()->create();
    Referral::factory()->create(['agency_id' => $this->agency->id, 'family_id' => $family->id, 'status' => 'pending']);
    Referral::factory()->create(['agency_id' => $this->agency->id, 'family_id' => $family->id, 'status' => 'converted']);

    $response = $this->actingAs($this->owner)->get(route('agency.leads.index', ['status' => 'converted']));

    $leads = $response->viewData('leads');
    expect($leads->total())->toBe(1);
});

test('family user cannot access agency lead inbox', function () {
    $family = User::factory()->create()->assignRole('family');

    $response = $this->actingAs($family)->get(route('agency.leads.index'));

    $response->assertStatus(403);
});

// ─── Public Directory ─────────────────────────────────────────────────────────

test('public directory shows only published agencies', function () {
    Agency::factory()->create(['status' => AgencyStatus::Published->value, 'name' => 'Visible Agency']);
    Agency::factory()->create(['status' => AgencyStatus::Draft->value, 'name' => 'Hidden Draft Agency']);

    $response = $this->get(route('agencies.index'));

    $response->assertOk();
    $response->assertSee('Visible Agency');
    $response->assertDontSee('Hidden Draft Agency');
});

test('public directory is accessible without authentication', function () {
    $response = $this->get(route('agencies.index'));

    $response->assertOk();
});

test('public agency detail page is viewable for a published agency', function () {
    $agency = Agency::factory()->create(['status' => AgencyStatus::Published->value, 'name' => 'Sunrise Manor']);

    $response = $this->get(route('agencies.show', $agency));

    $response->assertOk();
    $response->assertSee('Sunrise Manor');
});

test('draft agency detail page returns 404 to the public', function () {
    $agency = Agency::factory()->create(['status' => AgencyStatus::Draft->value]);

    $response = $this->get(route('agencies.show', $agency));

    $response->assertStatus(404);
});

test('public directory can be filtered by city', function () {
    Agency::factory()->create(['status' => AgencyStatus::Published->value, 'city' => 'Austin', 'name' => 'Austin Agency']);
    Agency::factory()->create(['status' => AgencyStatus::Published->value, 'city' => 'Dallas', 'name' => 'Dallas Agency']);

    $response = $this->get(route('agencies.index', ['city' => 'Austin']));

    $response->assertOk();
    $response->assertSee('Austin Agency');
    $response->assertDontSee('Dallas Agency');
});
