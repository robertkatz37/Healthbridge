<?php

use App\Models\AgencyCategory;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\City;
use App\Models\CmsPage;
use App\Models\State;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Database\Seeders\AdminUserSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('super_admin can view the location guides index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.cms.locations.index'));

    $response->assertOk();
    $response->assertSee('Texas');
});

test('super_admin can create a state guide with custom intro content', function () {
    $texas = State::where('code', 'TX')->first();

    $response = $this->actingAs($this->admin)->put(route('admin.cms.locations.states.update', $texas), [
        'intro_content' => 'Texas has a wide range of senior care options.',
        'status' => 'published',
        'meta_title' => 'Senior Care in Texas',
    ]);

    $response->assertRedirect();
    expect($texas->fresh()->guide)->not->toBeNull();
    expect($texas->fresh()->guide->intro_content)->toBe('Texas has a wide range of senior care options.');

    $publicResponse = $this->get('/texas');
    $publicResponse->assertSee('Texas has a wide range of senior care options.', false);
});

test('super_admin can create a city guide', function () {
    $texas = State::where('code', 'TX')->first();
    $houston = City::where('state_id', $texas->id)->where('name', 'Houston')->first();

    $response = $this->actingAs($this->admin)->put(route('admin.cms.locations.cities.update', $houston), [
        'intro_content' => 'Houston offers excellent memory care facilities.',
        'status' => 'published',
    ]);

    $response->assertRedirect();
    expect($houston->fresh()->guide->intro_content)->toBe('Houston offers excellent memory care facilities.');
});

test('super_admin can create a service guide', function () {
    $category = AgencyCategory::where('code', 'memory_care')->first();

    $response = $this->actingAs($this->admin)->put(route('admin.cms.locations.services.update', $category), [
        'intro_content' => 'Memory care provides specialized support for dementia.',
        'status' => 'published',
    ]);

    $response->assertRedirect();
    expect($category->fresh()->guide->intro_content)->toBe('Memory care provides specialized support for dementia.');
});

test('a guide slug is auto-generated and stable across updates', function () {
    $texas = State::where('code', 'TX')->first();

    $this->actingAs($this->admin)->put(route('admin.cms.locations.states.update', $texas), ['intro_content' => 'First version', 'status' => 'draft']);
    $firstSlug = $texas->fresh()->guide->slug;

    $this->actingAs($this->admin)->put(route('admin.cms.locations.states.update', $texas), ['intro_content' => 'Second version', 'status' => 'published']);
    $secondSlug = $texas->fresh()->guide->slug;

    expect($firstSlug)->toBe($secondSlug);
});

test('super_admin can view and update CMS settings', function () {
    $editResponse = $this->actingAs($this->admin)->get(route('admin.settings.cms.edit'));
    $editResponse->assertOk();

    $updateResponse = $this->actingAs($this->admin)->put(route('admin.settings.cms.update'), [
        'blog_enabled' => '1',
        'blog_comments_enabled' => '1',
        'blog_posts_per_page' => 15,
        'seo_default_title_suffix' => 'HB Care',
        'seo_default_meta_description' => 'A custom default description.',
        'seo_default_robots' => 'index,follow',
    ]);

    $updateResponse->assertRedirect();
    $settings = app(SettingsService::class);
    expect($settings->get('blog_posts_per_page'))->toEqual(15);
    expect($settings->get('seo_default_title_suffix'))->toBe('HB Care');
});

test('changing the default meta description affects a page with no override', function () {
    app(SettingsService::class)->set('seo_default_meta_description', 'A brand new platform default.', 'cms', 'string');
    $page = CmsPage::create(['title' => 'No Meta Page', 'body' => 'content', 'page_type' => 'custom', 'status' => 'published', 'slug' => 'no-meta-page']);

    $response = $this->get('/' . $page->slug);

    $response->assertOk();
    $response->assertSee('A brand new platform default.', false);
});

test('admin reports page shows CMS Analytics with published pages and posts counts', function () {
    CmsPage::create(['title' => 'Published Page', 'body' => 'content', 'page_type' => 'custom', 'status' => 'published', 'slug' => 'published-page']);
    $category = BlogCategory::create(['name' => 'Tips', 'slug' => 'tips']);
    BlogPost::create(['title' => 'Published Post', 'body' => 'content', 'blog_category_id' => $category->id, 'author_id' => $this->admin->id, 'status' => 'published', 'published_at' => now(), 'slug' => 'published-post']);

    $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

    $response->assertOk();
    $response->assertSee('Published Pages');
    $response->assertSee('Published Posts');
});

test('an advisor cannot access CMS settings or location guides', function () {
    $advisor = User::factory()->create()->assignRole('advisor');

    $this->actingAs($advisor)->get(route('admin.settings.cms.edit'))->assertStatus(403);
    $this->actingAs($advisor)->get(route('admin.cms.locations.index'))->assertStatus(403);
});
