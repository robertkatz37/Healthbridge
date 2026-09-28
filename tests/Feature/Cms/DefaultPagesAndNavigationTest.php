<?php

use App\Models\CmsPage;
use App\Models\Menu;

test('all 6 default pages are seeded and publicly visible', function () {
    $home = CmsPage::where('page_type', 'home')->first();
    expect($home)->not->toBeNull('Expected a seeded home page');
    expect($home->status)->toBe('published');
    $this->withoutVite()->get('/')->assertOk();

    foreach (['about', 'contact', 'privacy', 'terms', 'careers'] as $slug) {
        $page = CmsPage::where('slug', $slug)->first();
        expect($page)->not->toBeNull("Expected a seeded page for '{$slug}'");
        expect($page->status)->toBe('published');

        $response = $this->withoutVite()->get('/' . $slug);
        $response->assertOk();
    }
});

test('none of the default pages contain lorem ipsum or placeholder text', function () {
    foreach (CmsPage::all() as $page) {
        expect(strtolower($page->body))->not->toContain('lorem ipsum');
        expect(strtolower($page->body))->not->toContain('todo');
        expect(strtolower($page->body))->not->toContain('placeholder');
    }
});

test('every default page has real SEO meta, not empty fields', function () {
    foreach (CmsPage::with('seoMeta')->get() as $page) {
        expect($page->seoMeta)->not->toBeNull("Expected SEO meta for '{$page->slug}'");
        expect($page->seoMeta->meta_title)->not->toBeEmpty();
        expect($page->seoMeta->meta_description)->not->toBeEmpty();
    }
});

test('the homepage uses real Page Builder sections, not just a plain body', function () {
    $home = CmsPage::where('page_type', 'home')->first();

    expect($home->sections()->count())->toBeGreaterThan(0);
});

test('privacy and terms pages are marked noindex since they are legal boilerplate, not marketing content', function () {
    $privacy = CmsPage::where('slug', 'privacy')->first();
    $terms = CmsPage::where('slug', 'terms')->first();

    expect($privacy->seoMeta->robots)->toBe('noindex,follow');
    expect($terms->seoMeta->robots)->toBe('noindex,follow');
});

test('header and footer menus have real navigation items, not empty shells', function () {
    $header = Menu::where('slug', 'header')->first();
    $footer = Menu::where('slug', 'footer')->first();

    expect($header->items()->count())->toBeGreaterThan(0);
    expect($footer->items()->count())->toBeGreaterThan(0);
});

test('the header menu renders on every public page, not just the homepage', function () {
    foreach (['/about', '/contact', '/blog', '/locations', '/services'] as $path) {
        $response = $this->withoutVite()->get($path);
        $response->assertOk();
        $response->assertSee('Find Care');
    }
});

test('the footer legal links point at the seeded privacy and terms pages', function () {
    $response = $this->withoutVite()->get('/');

    $response->assertOk();
    $response->assertSee('/privacy', false);
    $response->assertSee('/terms', false);
});

test('re-running the seeders does not duplicate pages or menu items', function () {
    $this->seed(\Database\Seeders\CmsPageSeeder::class);
    $this->seed(\Database\Seeders\MenuSeeder::class);

    expect(CmsPage::count())->toBe(6);
    expect(Menu::where('slug', 'header')->first()->items()->count())->toBe(5);
});
