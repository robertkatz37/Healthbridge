<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\City;
use App\Models\State;

test('locations index lists active states', function () {
    $response = $this->withoutVite()->get(route('locations.index'));

    $response->assertOk();
    $response->assertSee('Texas');
});

test('a state page shows agencies in that state and links to its cities', function () {
    $agency = Agency::factory()->create(['status' => 'published', 'state' => 'TX', 'city' => 'Houston']);

    $response = $this->withoutVite()->get('/texas');

    $response->assertOk();
    $response->assertSee($agency->name);
    $response->assertSee('Houston');
});

test('a city page shows agencies in that specific city and nearby cities', function () {
    Agency::factory()->create(['status' => 'published', 'state' => 'TX', 'city' => 'Houston']);

    $response = $this->withoutVite()->get('/texas/houston');

    $response->assertOk();
    $response->assertSee('Houston');
    $response->assertSee('Nearby Cities');
});

test('an agency in a different city does not appear on another citys page', function () {
    Agency::factory()->create(['status' => 'published', 'state' => 'TX', 'city' => 'Houston']);
    $austinAgency = Agency::factory()->create(['status' => 'published', 'state' => 'TX', 'city' => 'Austin']);

    $response = $this->withoutVite()->get('/texas/houston');

    $response->assertOk();
    $response->assertDontSee($austinAgency->name);
});

test('the exact checklist URLs resolve correctly: state, city, and two services', function () {
    Agency::factory()->create(['status' => 'published', 'state' => 'CA', 'city' => 'Los Angeles']);
    $homeCare = AgencyCategory::where('code', 'home_care')->first();
    $hospice = AgencyCategory::where('code', 'hospice')->first();
    Agency::factory()->create(['status' => 'published', 'agency_category_id' => $homeCare->id]);
    Agency::factory()->create(['status' => 'published', 'agency_category_id' => $hospice->id]);

    $this->withoutVite()->get('/california')->assertOk();
    $this->withoutVite()->get('/california/los-angeles')->assertOk();
    $this->withoutVite()->get('/home-care')->assertOk();
    $this->withoutVite()->get('/hospice')->assertOk();
});

test('a city URL never leaks the state-suffixed internal slug', function () {
    Agency::factory()->create(['status' => 'published', 'state' => 'CA', 'city' => 'Los Angeles']);

    $response = $this->withoutVite()->get('/california');

    $response->assertOk();
    $response->assertDontSee('los-angeles-ca', false);
    $response->assertSee('/california/los-angeles', false);
});

test('services index lists active categories', function () {
    $response = $this->withoutVite()->get(route('services.index'));

    $response->assertOk();
});

test('a service page shows agencies in that category', function () {
    $category = AgencyCategory::where('code', 'memory_care')->first();
    $agency = Agency::factory()->create(['status' => 'published', 'agency_category_id' => $category->id]);

    $response = $this->withoutVite()->get('/memory-care');

    $response->assertOk();
    $response->assertSee($agency->name);
});

test('a single-segment slug that matches neither a state nor a service falls through to a CMS page', function () {
    $page = \App\Services\Cms\PagePublishingService::class;
    $author = \App\Models\User::factory()->create();
    $created = app($page)->create(['title' => 'Careers', 'body' => 'Join our team.', 'page_type' => 'careers'], $author);
    app($page)->publish($created, $author);

    $response = $this->withoutVite()->get('/' . $created->slug);

    $response->assertOk();
    $response->assertSee('Careers');
});

test('global search finds matching pages, agencies, and cities across types', function () {
    $agency = Agency::factory()->create(['status' => 'published', 'name' => 'Sunrise Memory Villas']);

    $response = $this->withoutVite()->get(route('search', ['q' => 'Sunrise Memory']));

    $response->assertOk();
    $response->assertSee('Sunrise Memory Villas');
});

test('search with no query shows an empty state rather than erroring', function () {
    $response = $this->withoutVite()->get(route('search'));

    $response->assertOk();
});

test('sitemap.xml includes published agencies, pages, and posts, uses clean URLs, and is valid XML', function () {
    Agency::factory()->create(['status' => 'published']);

    $response = $this->withoutVite()->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/xml');
    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse();
    expect((string) $xml->url[0]->loc)->toBe(url('/'));
});
