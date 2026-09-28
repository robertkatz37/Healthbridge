<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cms\StorePageRequest;
use App\Http\Requests\Admin\Cms\UpdatePageRequest;
use App\Models\CmsPage;
use App\Services\Cms\PagePublishingService;
use App\Services\Cms\SeoMetaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly PagePublishingService $publishing,
        private readonly SeoMetaService $seo,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CmsPage::class);

        $query = CmsPage::query()->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $pages = $query->paginate(20)->withQueryString();

        return view('admin.cms.pages.index', compact('pages'));
    }

    public function create(): View
    {
        $this->authorize('create', CmsPage::class);

        return view('admin.cms.pages.create');
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $page = $this->publishing->create($request->validated(), $request->user());

        $this->syncSections($page, $request->input('sections', []));
        $this->seo->upsertFor($page, $request->only(['meta_title', 'meta_description', 'og_image', 'canonical_url', 'robots']));

        activity()->causedBy($request->user())->performedOn($page)->log('Page created');

        return redirect()->route('admin.cms.pages.edit', $page)->with('status', 'page-created');
    }

    public function edit(CmsPage $page): View
    {
        $this->authorize('update', $page);

        $page->load(['sections' => fn ($q) => $q->orderBy('sort_order'), 'seoMeta', 'faqs', 'revisions.editor']);

        return view('admin.cms.pages.edit', compact('page'));
    }

    public function update(UpdatePageRequest $request, CmsPage $page): RedirectResponse
    {
        $this->publishing->update($page, $request->validated(), $request->user());

        $this->syncSections($page, $request->input('sections', []));
        $this->seo->upsertFor($page, $request->only(['meta_title', 'meta_description', 'og_image', 'canonical_url', 'robots']));

        activity()->causedBy($request->user())->performedOn($page)->log('Page updated');

        return back()->with('status', 'page-updated');
    }

    public function publish(Request $request, CmsPage $page): RedirectResponse
    {
        $this->authorize('update', $page);
        $this->publishing->publish($page, $request->user());

        return back()->with('status', 'page-published');
    }

    public function schedule(Request $request, CmsPage $page): RedirectResponse
    {
        $this->authorize('update', $page);
        $request->validate(['scheduled_at' => ['required', 'date', 'after:now']]);
        $this->publishing->schedule($page, \Carbon\Carbon::parse($request->scheduled_at), $request->user());

        return back()->with('status', 'page-scheduled');
    }

    public function archive(Request $request, CmsPage $page): RedirectResponse
    {
        $this->authorize('update', $page);
        $this->publishing->archive($page, $request->user());

        return back()->with('status', 'page-archived');
    }

    public function revertToDraft(Request $request, CmsPage $page): RedirectResponse
    {
        $this->authorize('update', $page);
        $this->publishing->revertToDraft($page, $request->user());

        return back()->with('status', 'page-reverted');
    }

    public function restoreRevision(Request $request, CmsPage $page, \App\Models\PageRevision $revision): RedirectResponse
    {
        $this->authorize('update', $page);
        abort_unless($revision->cms_page_id === $page->id, 404);

        $this->publishing->restore($page, $revision, $request->user());

        return back()->with('status', 'page-restored');
    }

    public function destroy(Request $request, CmsPage $page): RedirectResponse
    {
        $this->authorize('delete', $page);
        activity()->causedBy($request->user())->performedOn($page)->log('Page deleted');
        $page->delete();

        return redirect()->route('admin.cms.pages.index')->with('status', 'page-deleted');
    }

    private function syncSections(CmsPage $page, array $sections): void
    {
        $page->sections()->delete();
        foreach ($sections as $index => $section) {
            if (empty($section['type'])) {
                continue;
            }
            $page->sections()->create([
                'type' => $section['type'],
                'content' => $section['content'] ?? [],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }
}
