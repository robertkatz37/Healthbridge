<?php

namespace App\Services\Cms;

use App\Models\CmsPage;
use App\Models\PageRevision;
use App\Models\User;
use Carbon\Carbon;

class PagePublishingService
{
    public function __construct(
        private readonly SlugService $slugs,
    ) {}

    public function create(array $data, ?User $author = null): CmsPage
    {
        $data['slug'] = $data['slug'] ?? $this->slugs->unique(CmsPage::class, $data['title']);
        $data['author_id'] = $author?->id;

        return CmsPage::create($data);
    }

    public function update(CmsPage $page, array $data, ?User $editor = null): void
    {
        $this->recordRevision($page, $editor);

        if (isset($data['title']) && $data['title'] !== $page->title && empty($data['slug'])) {
            $data['slug'] = $this->slugs->unique(CmsPage::class, $data['title'], $page->id);
        }

        $page->update($data);
    }

    public function recordRevision(CmsPage $page, ?User $editor = null): void
    {
        $page->revisions()->create([
            'edited_by' => $editor?->id,
            'title' => $page->title,
            'body' => $page->body,
            'sections_snapshot' => $page->sections()->orderBy('sort_order')->get()->toArray(),
        ]);
    }

    public function publish(CmsPage $page, ?User $actor = null): void
    {
        $page->update(['status' => 'published', 'published_at' => $page->published_at ?? now(), 'scheduled_at' => null]);
        activity()->causedBy($actor)->performedOn($page)->log('Page published');
    }

    public function schedule(CmsPage $page, Carbon $at, ?User $actor = null): void
    {
        $page->update(['status' => 'scheduled', 'scheduled_at' => $at]);
        activity()->causedBy($actor)->performedOn($page)->log('Page scheduled for ' . $at->toDateTimeString());
    }

    public function archive(CmsPage $page, ?User $actor = null): void
    {
        $page->update(['status' => 'archived']);
        activity()->causedBy($actor)->performedOn($page)->log('Page archived');
    }

    public function revertToDraft(CmsPage $page, ?User $actor = null): void
    {
        $page->update(['status' => 'draft', 'scheduled_at' => null]);
        activity()->causedBy($actor)->performedOn($page)->log('Page reverted to draft');
    }

    public function promoteDuePages(): int
    {
        return CmsPage::where('status', 'scheduled')->where('scheduled_at', '<=', now())
            ->update(['status' => 'published']);
    }

    /**
     * Restores a page to a prior revision's title/body/sections. The
     * page's CURRENT state is archived as a new revision first — same
     * as any other update — so restoring is itself undoable, and the
     * revision history never loses a step even when jumping backward.
     */
    public function restore(CmsPage $page, PageRevision $revision, ?User $actor = null): void
    {
        $this->recordRevision($page, $actor);

        $page->update(['title' => $revision->title, 'body' => $revision->body]);

        $page->sections()->delete();
        foreach ($revision->sections_snapshot ?? [] as $index => $section) {
            $page->sections()->create([
                'type' => $section['type'],
                'content' => $section['content'] ?? [],
                'sort_order' => $section['sort_order'] ?? $index,
                'is_active' => $section['is_active'] ?? true,
            ]);
        }

        activity()->causedBy($actor)->performedOn($page)->log('Page restored to revision from ' . $revision->created_at->toDateTimeString());
    }
}
