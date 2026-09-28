<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can('cms.manage'), 403);

        $menus = Menu::with(['items' => fn ($q) => $q->orderBy('sort_order')])->get()->keyBy('slug');
        $pages = CmsPage::orderBy('title')->get(['id', 'title', 'slug']);

        return view('admin.cms.menus.index', compact('menus', 'pages'));
    }

    public function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'cms_page_id' => ['nullable', 'exists:cms_pages,id'],
            'url' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'target' => ['nullable', 'in:_self,_blank'],
        ]);

        $maxSort = MenuItem::where('menu_id', $menu->id)->max('sort_order') ?? 0;

        $item = $menu->items()->create([
            'parent_id' => $request->parent_id,
            'label' => $request->label,
            'cms_page_id' => $request->cms_page_id,
            'url' => $request->url,
            'target' => $request->target ?? '_self',
            'sort_order' => $maxSort + 1,
        ]);

        activity()->causedBy($request->user())->performedOn($menu)->log('Menu item added: ' . $item->label);

        return back()->with('status', 'menu-item-added');
    }

    public function updateItem(Request $request, MenuItem $item): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'cms_page_id' => ['nullable', 'exists:cms_pages,id'],
            'url' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'in:_self,_blank'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $item->update([
            'label' => $request->label,
            'cms_page_id' => $request->cms_page_id,
            'url' => $request->url,
            'target' => $request->target ?? '_self',
            'is_active' => $request->boolean('is_active', true),
        ]);

        activity()->causedBy($request->user())->performedOn($item->menu)->log('Menu item updated: ' . $item->label);

        return back()->with('status', 'menu-item-updated');
    }

    public function reorder(Request $request, Menu $menu): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $request->validate(['order' => ['required', 'array'], 'order.*' => ['exists:menu_items,id']]);

        foreach ($request->order as $index => $itemId) {
            MenuItem::where('id', $itemId)->where('menu_id', $menu->id)->update(['sort_order' => $index]);
        }

        return back()->with('status', 'menu-reordered');
    }

    public function destroyItem(Request $request, MenuItem $item): RedirectResponse
    {
        abort_unless($request->user()->can('cms.manage'), 403);
        $menu = $item->menu;

        activity()->causedBy($request->user())->performedOn($menu)->log('Menu item removed: ' . $item->label);
        $item->delete();

        return back()->with('status', 'menu-item-deleted');
    }
}
