<?php

use App\Models\Agency;
use App\Models\AgencyCategory;
use App\Models\BlogCategory;
use App\Models\State;
use App\Models\User;
use App\Services\Cms\BlogPublishingService;
use App\Services\Cms\PagePublishingService;

/**
 * Walks the exact SEO checklist ("open page source and verify: meta
 * title, meta description, OG tags, Twitter tags, JSON-LD, canonical,
 * sitemap") across every page type the phase names, rather than
 * trusting that fixing it for one type means it's fixed everywhere.
 */
test('SEO CHECKLIST: meta/OG/Twitter/JSON-LD/canonical present on every public page type, and sitemap is valid', function () {
    $this->withoutVite();
    $author = User::factory()->create();

    $requiredTags = [
        '<meta name="description"',
        '<link rel="canonical"',
        '<meta name="robots"',
        'property="og:title"',
        'property="og:type"',
        'property="og:url"',
        'name="twitter:card"',
        'name="twitter:title"',
        'application/ld+json',
    ];

    // 1. CMS Page (About — seeded).
    $about = $this->get('/about');
    $about->assertOk();
    foreach ($requiredTags as $tag) {
        $about->assertSee($tag, false);
    }
    dump('1. CMS Page (/about): all required SEO tags present');

    // 2. Blog Post.
    $category = BlogCategory::create(['name' => 'SEO Test', 'slug' => 'seo-test']);
    $post = app(BlogPublishingService::class)->create(['title' => 'SEO Verification Article', 'body' => str_repeat('word ', 300), 'blog_category_id' => $category->id], $author);
    app(BlogPublishingService::class)->publish($post, $author);
    $blogResponse = $this->get('/blog/' . $post->slug);
    $blogResponse->assertOk();
    foreach ($requiredTags as $tag) {
        $blogResponse->assertSee($tag, false);
    }
    dump('2. Blog Post (/blog/' . $post->slug . '): all required SEO tags present');

    // 3. Agency Detail Page.
    $agency = Agency::factory()->create(['status' => 'published', 'name' => 'SEO Check Care Home']);
    $agencyResponse = $this->get('/agencies/' . $agency->slug);
    $agencyResponse->assertOk();
    foreach ($requiredTags as $tag) {
        $agencyResponse->assertSee($tag, false);
    }
    dump('3. Agency Detail Page (/agencies/' . $agency->slug . '): all required SEO tags present');

    // 4. State Page.
    Agency::factory()->create(['status' => 'published', 'state' => 'TX']);
    $stateResponse = $this->get('/texas');
    $stateResponse->assertOk();
    foreach ($requiredTags as $tag) {
        $stateResponse->assertSee($tag, false);
    }
    dump('4. State Page (/texas): all required SEO tags present');

    // 5. City Page.
    Agency::factory()->create(['status' => 'published', 'state' => 'TX', 'city' => 'Houston']);
    $cityResponse = $this->get('/texas/houston');
    $cityResponse->assertOk();
    foreach ($requiredTags as $tag) {
        $cityResponse->assertSee($tag, false);
    }
    dump('5. City Page (/texas/houston): all required SEO tags present');

    // 6. Service Page.
    $memoryCare = AgencyCategory::where('code', 'memory_care')->first();
    Agency::factory()->create(['status' => 'published', 'agency_category_id' => $memoryCare->id]);
    $serviceResponse = $this->get('/memory-care');
    $serviceResponse->assertOk();
    foreach ($requiredTags as $tag) {
        $serviceResponse->assertSee($tag, false);
    }
    dump('6. Service Page (/memory-care): all required SEO tags present');

    // 7. Sitemap. A plain foreach over $xml->url (not iterator_to_array,
    // which collapses same-named SimpleXML siblings by key and silently
    // drops all but the last one under each key) to correctly visit
    // every <url> entry.
    $sitemapResponse = $this->get('/sitemap.xml');
    $sitemapResponse->assertOk();
    expect($sitemapResponse->headers->get('Content-Type'))->toContain('application/xml');
    $xml = simplexml_load_string($sitemapResponse->getContent());
    expect($xml)->not->toBeFalse();
    $locs = [];
    foreach ($xml->url as $urlNode) {
        $locs[] = parse_url((string) $urlNode->loc, PHP_URL_PATH);
    }
    expect($locs)->toContain('/agencies/' . $agency->slug);
    expect($locs)->toContain('/blog/' . $post->slug);
    dump('7. Sitemap: valid XML, includes the agency and blog post created above (' . count($locs) . ' total URLs)');

    dump('=== SEO CHECKLIST — ALL 7 PAGE TYPES + SITEMAP VERIFIED ===');

    expect(true)->toBeTrue();
});
