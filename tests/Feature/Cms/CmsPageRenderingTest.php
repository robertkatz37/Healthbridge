<?php

use App\Models\CmsPage;
use App\Models\User;
use App\Services\Cms\PagePublishingService;

beforeEach(function () {
    $this->withoutVite();
    $this->author = User::factory()->create();
    $this->publishing = app(PagePublishingService::class);
});

test('a published simple page renders its body via the catch-all slug route', function () {
    $page = $this->publishing->create(['title' => 'Our Mission', 'body' => '<p>We connect families with care.</p>', 'page_type' => 'about'], $this->author);
    $this->publishing->publish($page, $this->author);

    $response = $this->get('/' . $page->slug);

    $response->assertOk();
    $response->assertSee('Our Mission');
});

test('a draft page 404s for a public visitor', function () {
    $page = $this->publishing->create(['title' => 'Draft Page', 'body' => 'Not ready yet', 'page_type' => 'custom', 'status' => 'draft'], $this->author);

    $response = $this->get('/' . $page->slug);

    $response->assertNotFound();
});

test('the homepage falls back to the default welcome view when no home page has been set up', function () {
    $response = $this->get('/');

    $response->assertOk();
});
