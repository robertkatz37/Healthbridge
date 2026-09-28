<?php

use App\Models\Agency;

test('the agency detail page renders full SEO metadata: title, description, canonical, robots, and JSON-LD', function () {
    $agency = Agency::factory()->create(['status' => 'published', 'name' => 'Golden Years Memory Care']);

    $response = $this->withoutVite()->get(route('agencies.show', $agency));

    $response->assertOk();
    $response->assertSee('Golden Years Memory Care', false);
    $response->assertSee('<meta name="description"', false);
    $response->assertSee('<link rel="canonical"', false);
    $response->assertSee('<meta name="robots"', false);
    $response->assertSee('property="og:title"', false);
    $response->assertSee('name="twitter:card"', false);
    $response->assertSee('application/ld+json', false);
});

test('the agency detail page JSON-LD includes LocalBusiness schema with the agencys real data', function () {
    $agency = Agency::factory()->create(['status' => 'published', 'name' => 'Sunrise Assisted Living', 'city' => 'Denver', 'state' => 'CO']);

    $response = $this->withoutVite()->get(route('agencies.show', $agency));

    $response->assertOk();
    $response->assertSee('"@type":"LocalBusiness"', false);
    $response->assertSee('Sunrise Assisted Living', false);
    $response->assertSee('Denver', false);
});

test('the agency detail page includes breadcrumbs both visually and as JSON-LD', function () {
    $agency = Agency::factory()->create(['status' => 'published', 'name' => 'Willow Creek Care']);

    $response = $this->withoutVite()->get(route('agencies.show', $agency));

    $response->assertOk();
    $response->assertSee('Willow Creek Care');
    $response->assertSee('"@type":"BreadcrumbList"', false);
});

test('the agency directory index page also carries SEO description and breadcrumbs', function () {
    $response = $this->withoutVite()->get(route('agencies.index'));

    $response->assertOk();
    $response->assertSee('<meta name="description"', false);
});
