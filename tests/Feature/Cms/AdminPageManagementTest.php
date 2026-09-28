<?php

use App\Models\CmsPage;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('super_admin can view the pages list and create page form', function () {
    $indexResponse = $this->actingAs($this->admin)->get(route('admin.cms.pages.index'));
    $indexResponse->assertOk();

    $createResponse = $this->actingAs($this->admin)->get(route('admin.cms.pages.create'));
    $createResponse->assertOk();
});

test('super_admin can create a page with sections and SEO meta', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.cms.pages.store'), [
        'title' => 'Our Careers',
        'body' => 'fallback body',
        'page_type' => 'careers',
        'template' => 'default',
        'status' => 'draft',
        'meta_title' => 'Careers at HealthsBridge',
        'meta_description' => 'Join our team.',
        'robots' => 'index,follow',
        'sections' => [
            ['type' => 'hero', 'content' => ['heading' => 'Join Our Team']],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();

    $page = CmsPage::where('title', 'Our Careers')->first();
    expect($page)->not->toBeNull();
    expect($page->sections()->count())->toBe(1);
    expect($page->seoMeta->meta_title)->toBe('Careers at HealthsBridge');
});

test('updating a page archives a revision of its previous content', function () {
    $page = CmsPage::create(['title' => 'Test Page', 'body' => 'Original content', 'page_type' => 'custom', 'status' => 'draft', 'slug' => 'test-page']);

    $response = $this->actingAs($this->admin)->put(route('admin.cms.pages.update', $page), [
        'title' => 'Test Page', 'body' => 'Updated content', 'page_type' => 'custom', 'status' => 'draft',
    ]);

    $response->assertRedirect();
    expect($page->revisions()->count())->toBe(1);
    expect($page->revisions()->first()->body)->toBe('Original content');
    expect($page->fresh()->body)->toBe('Updated content');
});

test('super_admin can publish, schedule, archive, and revert a page', function () {
    $page = CmsPage::create(['title' => 'Workflow Page', 'body' => 'content', 'page_type' => 'custom', 'status' => 'draft', 'slug' => 'workflow-page']);

    $this->actingAs($this->admin)->post(route('admin.cms.pages.publish', $page));
    expect($page->fresh()->status)->toBe('published');

    $this->actingAs($this->admin)->post(route('admin.cms.pages.schedule', $page), ['scheduled_at' => now()->addDay()->toDateTimeString()]);
    expect($page->fresh()->status)->toBe('scheduled');

    $this->actingAs($this->admin)->post(route('admin.cms.pages.archive', $page));
    expect($page->fresh()->status)->toBe('archived');

    $this->actingAs($this->admin)->post(route('admin.cms.pages.revert-to-draft', $page));
    expect($page->fresh()->status)->toBe('draft');
});

test('super_admin can delete a page', function () {
    $page = CmsPage::create(['title' => 'Deletable Page', 'body' => 'content', 'page_type' => 'custom', 'status' => 'draft', 'slug' => 'deletable-page']);

    $response = $this->actingAs($this->admin)->delete(route('admin.cms.pages.destroy', $page));

    $response->assertRedirect();
    expect(CmsPage::find($page->id))->toBeNull();
});

test('slugs auto-generate from title and stay unique on collision', function () {
    $this->actingAs($this->admin)->post(route('admin.cms.pages.store'), ['title' => 'About Us', 'body' => 'content', 'page_type' => 'about', 'status' => 'draft']);
    $this->actingAs($this->admin)->post(route('admin.cms.pages.store'), ['title' => 'About Us', 'body' => 'other content', 'page_type' => 'custom', 'status' => 'draft']);

    expect(CmsPage::where('slug', 'about-us')->exists())->toBeTrue();
    expect(CmsPage::where('slug', 'about-us-2')->exists())->toBeTrue();
});

test('a content_editor (not super_admin) can manage pages via the cms.manage permission', function () {
    $editor = User::factory()->create()->assignRole('content_editor');

    $response = $this->actingAs($editor)->post(route('admin.cms.pages.store'), [
        'title' => 'Editor Created Page', 'body' => 'content', 'page_type' => 'custom', 'status' => 'draft',
    ]);

    $response->assertRedirect();
    $response->assertSessionDoesntHaveErrors();
    expect(CmsPage::where('title', 'Editor Created Page')->exists())->toBeTrue();
});

test('an advisor cannot manage pages at all', function () {
    $advisor = User::factory()->create()->assignRole('advisor');

    $this->actingAs($advisor)->get(route('admin.cms.pages.index'))->assertStatus(403);
});

test('super_admin can restore a page to a prior revision, and the current state is saved first', function () {
    $page = \App\Models\CmsPage::create(['title' => 'Original Title', 'body' => 'Original body content.', 'page_type' => 'custom', 'status' => 'draft', 'slug' => 'restore-test']);

    // First edit — archives "Original" as revision 1.
    $this->actingAs($this->admin)->put(route('admin.cms.pages.update', $page), [
        'title' => 'Second Title', 'body' => 'Second body content.', 'page_type' => 'custom', 'status' => 'draft',
    ]);
    $firstRevision = $page->revisions()->first();
    expect($firstRevision->title)->toBe('Original Title');

    // Restore back to the original revision.
    $response = $this->actingAs($this->admin)->post(route('admin.cms.pages.revisions.restore', [$page, $firstRevision]));

    $response->assertRedirect();
    expect($page->fresh()->title)->toBe('Original Title');
    expect($page->fresh()->body)->toBe('Original body content.');

    // The "Second Title" state was archived as a new revision before restoring, so nothing is lost.
    expect($page->revisions()->count())->toBe(2);
    expect($page->revisions()->first()->title)->toBe('Second Title');
});

test('restoring a page also restores its Page Builder sections from that revisions snapshot', function () {
    $page = \App\Models\CmsPage::create(['title' => 'Sectioned Page', 'body' => 'fallback', 'page_type' => 'custom', 'status' => 'draft', 'slug' => 'sectioned-restore-test']);
    $page->sections()->create(['type' => 'hero', 'content' => ['heading' => 'Original Hero'], 'sort_order' => 0]);

    // Editing archives the current sections snapshot, then we clear sections.
    $this->actingAs($this->admin)->put(route('admin.cms.pages.update', $page), [
        'title' => 'Sectioned Page', 'body' => 'fallback', 'page_type' => 'custom', 'status' => 'draft', 'sections' => [],
    ]);
    expect($page->fresh()->sections()->count())->toBe(0);

    $revision = $page->revisions()->first();
    $response = $this->actingAs($this->admin)->post(route('admin.cms.pages.revisions.restore', [$page, $revision]));

    $response->assertRedirect();
    expect($page->fresh()->sections()->count())->toBe(1);
    expect($page->fresh()->sections()->first()->content['heading'])->toBe('Original Hero');
});
