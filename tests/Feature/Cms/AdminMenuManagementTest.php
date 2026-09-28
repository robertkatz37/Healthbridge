<?php

use App\Models\CmsPage;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('the header, footer, and mobile menus are seeded by default', function () {
    expect(Menu::where('slug', 'header')->exists())->toBeTrue();
    expect(Menu::where('slug', 'footer')->exists())->toBeTrue();
    expect(Menu::where('slug', 'mobile')->exists())->toBeTrue();
});

test('super_admin can view the menus management page', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.cms.menus.index'));

    $response->assertOk();
});

test('super_admin can add a menu item linking to a CMS page', function () {
    $menu = Menu::where('slug', 'header')->first();
    $page = CmsPage::create(['title' => 'Testimonials', 'body' => 'content', 'page_type' => 'custom', 'status' => 'published', 'slug' => 'testimonials']);

    $response = $this->actingAs($this->admin)->post(route('admin.cms.menus.items.store', $menu), [
        'label' => 'Testimonials Link', 'cms_page_id' => $page->id,
    ]);

    $response->assertRedirect();
    $item = MenuItem::where('label', 'Testimonials Link')->first();
    expect($item)->not->toBeNull();
    expect($item->resolved_url)->toBe('/testimonials');
});

test('a menu item pointing at a raw URL resolves to that URL', function () {
    $menu = Menu::where('slug', 'footer')->first();
    $item = $menu->items()->create(['label' => 'External Link', 'url' => 'https://example.com', 'sort_order' => 1]);

    expect($item->resolved_url)->toBe('https://example.com');
});

test('a menu item linking to the home page resolves to the root URL', function () {
    $menu = Menu::where('slug', 'header')->first();
    $homePage = CmsPage::where('page_type', 'home')->first();
    $item = $menu->items()->create(['label' => 'Home', 'cms_page_id' => $homePage->id, 'sort_order' => 0]);

    expect($item->resolved_url)->toBe('/');
});

test('the header menu with items renders on the public homepage', function () {
    $menu = Menu::where('slug', 'header')->first();
    $menu->items()->create(['label' => 'Our Agencies', 'url' => '/agencies', 'sort_order' => 1]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Our Agencies');
});

test('a nested menu item appears as a dropdown child under its parent', function () {
    $menu = Menu::where('slug', 'header')->first();
    $parent = $menu->items()->create(['label' => 'Resources', 'url' => '#', 'sort_order' => 1]);
    $menu->items()->create(['label' => 'Blog', 'url' => '/blog', 'parent_id' => $parent->id, 'sort_order' => 1]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Resources');
});

test('super_admin can remove a menu item', function () {
    $menu = Menu::where('slug', 'header')->first();
    $item = $menu->items()->create(['label' => 'Temp Link', 'url' => '/temp', 'sort_order' => 1]);

    $response = $this->actingAs($this->admin)->delete(route('admin.cms.menus.items.destroy', $item));

    $response->assertRedirect();
    expect(MenuItem::find($item->id))->toBeNull();
});

test('an advisor cannot manage menus', function () {
    $advisor = User::factory()->create()->assignRole('advisor');

    $this->actingAs($advisor)->get(route('admin.cms.menus.index'))->assertStatus(403);
});
