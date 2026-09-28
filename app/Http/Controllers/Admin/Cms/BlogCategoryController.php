<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Services\Cms\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    public function __construct(
        private readonly SlugService $slugs,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('cms.manage'), 403);

        $categories = BlogCategory::withCount('posts')->orderBy('name')->get();

        return view('admin.cms.blog.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate(['name' => ['required', 'string', 'max:100']]);

        $category = BlogCategory::create([
            'name' => $request->name,
            'slug' => $this->slugs->unique(BlogCategory::class, $request->name),
        ]);

        activity()->causedBy($request->user())->performedOn($category)->log('Blog category created');

        return back()->with('status', 'category-created');
    }

    public function update(Request $request, BlogCategory $category): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate(['name' => ['required', 'string', 'max:100']]);

        $category->update(['name' => $request->name]);
        activity()->causedBy($request->user())->performedOn($category)->log('Blog category updated');

        return back()->with('status', 'category-updated');
    }

    public function destroy(Request $request, BlogCategory $category): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);

        if ($category->posts()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete a category that still has posts. Reassign them first.']);
        }

        activity()->causedBy($request->user())->performedOn($category)->log('Blog category deleted');
        $category->delete();

        return back()->with('status', 'category-deleted');
    }
}
