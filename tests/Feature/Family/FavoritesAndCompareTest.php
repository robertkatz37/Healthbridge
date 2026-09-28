<?php

use App\Enums\AgencyStatus;
use App\Models\Agency;
use App\Models\Family;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create()->assignRole('family');
    $this->family = Family::create(['user_id' => $this->user->id]);
});

test('family can favorite an agency', function () {
    $agency = Agency::factory()->create(['status' => AgencyStatus::Published->value]);

    $response = $this->actingAs($this->user)->post(route('family.favorites.toggle', $agency));

    $response->assertRedirect();
    expect($this->family->favorites()->where('agency_id', $agency->id)->exists())->toBeTrue();
});

test('toggling favorite twice removes it', function () {
    $agency = Agency::factory()->create(['status' => AgencyStatus::Published->value]);

    $this->actingAs($this->user)->post(route('family.favorites.toggle', $agency));
    $this->actingAs($this->user)->post(route('family.favorites.toggle', $agency));

    expect($this->family->favorites()->where('agency_id', $agency->id)->exists())->toBeFalse();
});

test('family can view their favorites list', function () {
    $agency = Agency::factory()->create(['status' => AgencyStatus::Published->value, 'name' => 'Sunrise Manor']);
    $this->family->favorites()->create(['agency_id' => $agency->id]);

    $response = $this->actingAs($this->user)->get(route('family.favorites.index'));

    $response->assertOk();
    $response->assertSee('Sunrise Manor');
});

test('compare requires at least 2 agencies', function () {
    $agency = Agency::factory()->create(['status' => AgencyStatus::Published->value]);

    $response = $this->actingAs($this->user)->get(route('family.compare', ['agencies' => [$agency->id]]));

    $response->assertSessionHasErrors('agencies');
});

test('compare shows selected agencies side by side', function () {
    $a1 = Agency::factory()->create(['status' => AgencyStatus::Published->value, 'name' => 'Agency One']);
    $a2 = Agency::factory()->create(['status' => AgencyStatus::Published->value, 'name' => 'Agency Two']);

    $response = $this->actingAs($this->user)->get(route('family.compare', ['agencies' => [$a1->id, $a2->id]]));

    $response->assertOk();
    $response->assertSee('Agency One');
    $response->assertSee('Agency Two');
});

test('compare is capped at 4 agencies', function () {
    $agencies = Agency::factory()->count(5)->create(['status' => AgencyStatus::Published->value]);

    $response = $this->actingAs($this->user)->get(route('family.compare', ['agencies' => $agencies->pluck('id')->toArray()]));

    $response->assertSessionHasErrors('agencies');
});
