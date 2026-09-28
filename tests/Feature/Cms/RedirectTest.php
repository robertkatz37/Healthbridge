<?php

use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('a configured redirect actually redirects a visitor and increments its hit count', function () {
    $redirect = Redirect::create(['from_path' => 'old-about', 'to_path' => '/about-us', 'status_code' => 301]);

    $response = $this->get('/old-about');

    $response->assertRedirect(url('/about-us'));
    $response->assertStatus(301);
    expect($redirect->fresh()->hit_count)->toBe(1);
});

test('a redirect to an external URL works correctly', function () {
    Redirect::create(['from_path' => 'external-link', 'to_path' => 'https://example.com', 'status_code' => 302]);

    $response = $this->get('/external-link');

    $response->assertRedirect('https://example.com');
    $response->assertStatus(302);
});

test('an inactive redirect does not redirect', function () {
    Redirect::create(['from_path' => 'inactive-path', 'to_path' => '/somewhere', 'status_code' => 301, 'is_active' => false]);

    $response = $this->get('/inactive-path');

    $response->assertNotFound();
});

test('super_admin can view the redirects list and create one via the admin panel', function () {
    $indexResponse = $this->actingAs($this->admin)->get(route('admin.cms.redirects.index'));
    $indexResponse->assertOk();

    $response = $this->actingAs($this->admin)->post(route('admin.cms.redirects.store'), [
        'from_path' => 'old-page', 'to_path' => '/new-page', 'status_code' => 301,
    ]);

    $response->assertRedirect();
    expect(Redirect::where('from_path', 'old-page')->exists())->toBeTrue();
});

test('a duplicate from_path is rejected by validation', function () {
    Redirect::create(['from_path' => 'existing-path', 'to_path' => '/somewhere', 'status_code' => 301]);

    $response = $this->actingAs($this->admin)->post(route('admin.cms.redirects.store'), [
        'from_path' => 'existing-path', 'to_path' => '/elsewhere', 'status_code' => 301,
    ]);

    $response->assertSessionHasErrors('from_path');
});

test('super_admin can delete a redirect', function () {
    $redirect = Redirect::create(['from_path' => 'to-delete', 'to_path' => '/x', 'status_code' => 301]);

    $response = $this->actingAs($this->admin)->delete(route('admin.cms.redirects.destroy', $redirect));

    $response->assertRedirect();
    expect(Redirect::find($redirect->id))->toBeNull();
});

test('a redirect rule never intercepts a POST/PUT/DELETE request to the same path, only GET/HEAD', function () {
    Redirect::create(['from_path' => 'agency/billing/checkout', 'to_path' => '/some-other-page', 'status_code' => 301]);

    $owner = User::factory()->create()->assignRole('agency_owner');
    $category = \App\Models\AgencyCategory::first();
    $agency = app(\App\Services\Agency\AgencyProvisioningService::class)->createDraftAgency($owner, ['agency_category_id' => $category->id, 'name' => 'Redirect Safety Agency']);
    $agency->update(['status' => 'published']);
    $owner->update(['current_agency_id' => $agency->id]);

    $plan = \App\Models\Plan::where('code', 'premium')->first();

    Http::fake(['api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_redirect_safety', 'url' => 'https://checkout.stripe.com/pay/cs_redirect_safety'], 200)]);

    $response = $this->actingAs($owner)->post(route('agency.billing.checkout'), ['plan_id' => $plan->id, 'billing_cycle' => 'monthly']);

    // Must reach the real checkout controller and redirect to Stripe —
    // NOT the configured redirect rule's to_path.
    $response->assertRedirect('https://checkout.stripe.com/pay/cs_redirect_safety');
});
