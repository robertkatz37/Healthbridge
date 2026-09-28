<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\Cms\BreadcrumbService;
use App\Services\Cms\SeoMetaService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly SeoMetaService $seo,
    ) {}

    private function assertBlogEnabled(): void
    {
        abort_unless($this->settings->get('blog_enabled', true), 404);
    }

    public function index(Request $request): View
    {
        $this->assertBlogEnabled();

        $perPage = (int) $this->settings->get('blog_posts_per_page', 12);
        $query = BlogPost::visible()->with(['category', 'author'])->latest('published_at');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('body', 'like', $term)->orWhere('excerpt', 'like', $term));
        }

        $posts = $query->paginate($perPage)->withQueryString();
        $featuredPosts = BlogPost::visible()->featured()->latest('published_at')->limit(3)->get();
        $categories = BlogCategory::withCount('posts')->orderBy('name')->get();
        $tags = BlogTag::withCount('posts')->orderBy('name')->get();

        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add('Blog');

        return view('cms.blog.index', compact('posts', 'featuredPosts', 'categories', 'tags', 'breadcrumbs'));
    }

    public function show(BlogPost $post): View
    {
        $this->assertBlogEnabled();
        abort_unless($post->isVisible(), 404);

        $post->load(['category', 'author', 'tags', 'comments.user', 'comments.replies.user', 'seoMeta']);
        $relatedPosts = $post->relatedPosts();
        $commentsEnabled = $this->settings->get('blog_comments_enabled', false);

        $breadcrumbs = BreadcrumbService::make()->add('Home', '/')->add('Blog', route('blog.index'))->add($post->title);
        $seoTitle = $this->seo->resolveMetaTitle($post->seoMeta, $post->title);
        $seoDescription = $this->seo->resolveMetaDescription($post->seoMeta, $post->excerpt);
        $articleSchema = $this->seo->articleSchema($post);
        $breadcrumbSchema = $breadcrumbs->schema();

        return view('cms.blog.show', compact(
            'post', 'relatedPosts', 'commentsEnabled', 'breadcrumbs', 'seoTitle', 'seoDescription', 'articleSchema', 'breadcrumbSchema'
        ));
    }
}
