<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\BlogTag;
use App\Services\Cms\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogTagController extends Controller
{
    public function __construct(
        private readonly SlugService $slugs,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('cms.manage'), 403);

        $tags = BlogTag::withCount('posts')->orderBy('name')->get();

        return view('admin.cms.blog.tags.index', compact('tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate(['name' => ['required', 'string', 'max:50']]);

        $tag = BlogTag::create([
            'name' => $request->name,
            'slug' => $this->slugs->unique(BlogTag::class, $request->name),
        ]);

        activity()->causedBy($request->user())->performedOn($tag)->log('Blog tag created');

        return back()->with('status', 'tag-created');
    }

    public function destroy(Request $request, BlogTag $tag): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);

        activity()->causedBy($request->user())->performedOn($tag)->log('Blog tag deleted');
        $tag->delete();

        return back()->with('status', 'tag-deleted');
    }
}
