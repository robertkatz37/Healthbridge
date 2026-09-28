<?php

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(AdminUserSeeder::class);
    $this->admin = User::where('email', 'admin@healthsbridge.test')->first();
});

test('BLOG CHECKLIST: create category, create tag, publish post, schedule post, mark featured, related posts, search', function () {
    // 1. Create a category.
    $categoryResponse = $this->actingAs($this->admin)->post(route('admin.cms.blog.categories.store'), ['name' => 'Caregiver Tips']);
    $categoryResponse->assertRedirect();
    $category = BlogCategory::where('name', 'Caregiver Tips')->first();
    expect($category)->not->toBeNull();
    dump('1. Category created: ' . $category->name . ' (slug=' . $category->slug . ')');

    // 2. Create a tag.
    $tagResponse = $this->actingAs($this->admin)->post(route('admin.cms.blog.tags.store'), ['name' => 'Dementia Care']);
    $tagResponse->assertRedirect();
    $tag = BlogTag::where('name', 'Dementia Care')->first();
    expect($tag)->not->toBeNull();
    dump('2. Tag created: ' . $tag->name . ' (slug=' . $tag->slug . ')');

    // 3. Publish a post (using the category and tag).
    $publishResponse = $this->actingAs($this->admin)->post(route('admin.cms.blog.posts.store'), [
        'title' => 'Understanding Dementia-Friendly Home Design',
        'body' => str_repeat('Practical guidance for adapting a home for a loved one with dementia. ', 60),
        'excerpt' => 'Simple, practical changes that make a real difference.',
        'blog_category_id' => $category->id,
        'tag_ids' => [$tag->id],
        'status' => 'published',
    ]);
    $publishResponse->assertRedirect();
    $post = BlogPost::where('title', 'Understanding Dementia-Friendly Home Design')->first();
    expect($post->status)->toBe('published');
    expect($post->category_id ?? $post->blog_category_id)->toBe($category->id);
    expect($post->tags()->count())->toBe(1);
    dump('3. Post published: "' . $post->title . '", status=' . $post->status);

    $publicPost = $this->get(route('blog.show', $post));
    $publicPost->assertOk();
    $publicPost->assertSee($post->title);
    dump('   -> publicly visible at /blog/' . $post->slug);

    // 4. Schedule a post.
    $scheduleCreateResponse = $this->actingAs($this->admin)->post(route('admin.cms.blog.posts.store'), [
        'title' => 'Five Questions to Ask on a Memory Care Tour',
        'body' => str_repeat('What to look for and ask when touring a memory care facility. ', 60),
        'blog_category_id' => $category->id,
        'status' => 'draft',
    ]);
    $scheduledPost = BlogPost::where('title', 'Five Questions to Ask on a Memory Care Tour')->first();
    $scheduleResponse = $this->actingAs($this->admin)->post(route('admin.cms.blog.posts.schedule', $scheduledPost), [
        'published_at' => now()->addWeek()->toDateTimeString(),
    ]);
    $scheduleResponse->assertRedirect();
    expect($scheduledPost->fresh()->status)->toBe('scheduled');
    dump('4. Post scheduled: "' . $scheduledPost->title . '" for ' . $scheduledPost->fresh()->published_at);

    $scheduledPublicCheck = $this->get(route('blog.show', $scheduledPost));
    $scheduledPublicCheck->assertNotFound();
    dump('   -> correctly 404s publicly until its scheduled time arrives');

    // 5. Mark Featured.
    $featureResponse = $this->actingAs($this->admin)->put(route('admin.cms.blog.posts.update', $post), [
        'title' => $post->title, 'body' => $post->body, 'blog_category_id' => $category->id,
        'status' => 'published', 'is_featured' => '1',
    ]);
    $featureResponse->assertRedirect();
    expect($post->fresh()->is_featured)->toBeTrue();
    dump('5. Post marked Featured: is_featured=' . ($post->fresh()->is_featured ? 'true' : 'false'));

    $featuredOnIndex = $this->get(route('blog.index'));
    $featuredOnIndex->assertOk();
    $featuredOnIndex->assertSee('Featured');
    dump('   -> appears in the Featured section on /blog');

    // 6. Check Related Posts.
    $relatedPost = BlogPost::create([
        'title' => 'Nutrition Tips for Dementia Patients', 'body' => str_repeat('Nutrition guidance. ', 60),
        'blog_category_id' => $category->id, 'author_id' => $this->admin->id, 'status' => 'published', 'published_at' => now(), 'slug' => 'nutrition-tips-dementia',
    ]);
    $relatedPost->tags()->attach($tag->id);

    $postWithRelated = $this->get(route('blog.show', $post));
    $postWithRelated->assertOk();
    $postWithRelated->assertSee('Related Articles');
    $postWithRelated->assertSee('Nutrition Tips for Dementia Patients');
    dump('6. Related Posts: "Nutrition Tips for Dementia Patients" appears under ' . $post->title . ' (shares category + tag)');

    // 7. Search.
    $searchResponse = $this->get(route('blog.index', ['q' => 'Dementia-Friendly']));
    $searchResponse->assertOk();
    $searchResponse->assertSee($post->title);
    dump('7. Blog search for "Dementia-Friendly" finds the post');

    $globalSearchResponse = $this->get(route('search', ['q' => 'Dementia-Friendly Home Design']));
    $globalSearchResponse->assertOk();
    $globalSearchResponse->assertSee($post->title);
    dump('   -> also findable via global site search');

    dump('=== BLOG CHECKLIST — ALL 7 STEPS VERIFIED ===');

    expect(true)->toBeTrue();
});
