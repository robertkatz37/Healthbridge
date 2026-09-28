<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\StoreBlogPostRequest;
use App\Http\Requests\Admin\Cms\UpdateBlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\Cms\BlogPublishingService;
use App\Services\Cms\SeoMetaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function __construct(
        private readonly BlogPublishingService $publishing,
        private readonly SeoMetaService $seo,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BlogPost::class);

        $query = BlogPost::with(['category', 'author'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $posts = $query->paginate(20)->withQueryString();

        return view('admin.cms.blog.posts.index', compact('posts'));
    }

    public function create(): View
    {
        $this->authorize('create', BlogPost::class);

        $categories = BlogCategory::orderBy('name')->get();
        $tags = BlogTag::orderBy('name')->get();

        return view('admin.cms.blog.posts.create', compact('categories', 'tags'));
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('blog-featured', 'public');
        }

        $post = $this->publishing->create($data, $request->user());
        $this->seo->upsertFor($post, $request->only(['meta_title', 'meta_description']));

        activity()->causedBy($request->user())->performedOn($post)->log('Blog post created');

        return redirect()->route('admin.cms.blog.posts.edit', $post)->with('status', 'post-created');
    }

    public function edit(BlogPost $post): View
    {
        $this->authorize('update', $post);

        $post->load(['tags', 'seoMeta']);
        $categories = BlogCategory::orderBy('name')->get();
        $tags = BlogTag::orderBy('name')->get();

        return view('admin.cms.blog.posts.edit', compact('post', 'categories', 'tags'));
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post): RedirectResponse
    {
        $data = $request->validated();
        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('blog-featured', 'public');
        }

        $this->publishing->update($post, $data);
        $this->seo->upsertFor($post, $request->only(['meta_title', 'meta_description']));

        activity()->causedBy($request->user())->performedOn($post)->log('Blog post updated');

        return back()->with('status', 'post-updated');
    }

    public function publish(Request $request, BlogPost $post): RedirectResponse
    {
        $this->authorize('update', $post);
        $this->publishing->publish($post, $request->user());

        return back()->with('status', 'post-published');
    }

    public function schedule(Request $request, BlogPost $post): RedirectResponse
    {
        $this->authorize('update', $post);
        $request->validate(['published_at' => ['required', 'date', 'after:now']]);
        $this->publishing->schedule($post, \Carbon\Carbon::parse($request->published_at), $request->user());

        return back()->with('status', 'post-scheduled');
    }

    public function destroy(Request $request, BlogPost $post): RedirectResponse
    {
        $this->authorize('delete', $post);
        activity()->causedBy($request->user())->performedOn($post)->log('Blog post deleted');
        $post->delete();

        return redirect()->route('admin.cms.blog.posts.index')->with('status', 'post-deleted');
    }
}
