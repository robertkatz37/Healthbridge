<?php

namespace App\Services\Cms;

use App\Models\BlogPost;
use App\Models\User;
use Carbon\Carbon;

class BlogPublishingService
{
    public function __construct(
        private readonly SlugService $slugs,
    ) {}

    public function create(array $data, ?User $author = null): BlogPost
    {
        $data['slug'] = $data['slug'] ?? $this->slugs->unique(BlogPost::class, $data['title']);
        $data['author_id'] = $author?->id ?? $data['author_id'] ?? null;

        $tagIds = $data['tag_ids'] ?? [];
        unset($data['tag_ids']);

        $post = BlogPost::create($data);
        if (!empty($tagIds)) {
            $post->tags()->sync($tagIds);
        }

        return $post;
    }

    public function update(BlogPost $post, array $data): void
    {
        if (isset($data['title']) && $data['title'] !== $post->title && empty($data['slug'])) {
            $data['slug'] = $this->slugs->unique(BlogPost::class, $data['title'], $post->id);
        }

        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);

        $post->update($data);
        if ($tagIds !== null) {
            $post->tags()->sync($tagIds);
        }
    }

    public function publish(BlogPost $post, ?User $actor = null): void
    {
        $post->update(['status' => 'published', 'published_at' => $post->published_at ?? now()]);
        activity()->causedBy($actor)->performedOn($post)->log('Blog post published');
    }

    public function schedule(BlogPost $post, Carbon $at, ?User $actor = null): void
    {
        $post->update(['status' => 'scheduled', 'published_at' => $at]);
        activity()->causedBy($actor)->performedOn($post)->log('Blog post scheduled for ' . $at->toDateTimeString());
    }

    public function revertToDraft(BlogPost $post, ?User $actor = null): void
    {
        $post->update(['status' => 'draft']);
        activity()->causedBy($actor)->performedOn($post)->log('Blog post reverted to draft');
    }

    public function promoteDuePosts(): int
    {
        return BlogPost::where('status', 'scheduled')->where('published_at', '<=', now())
            ->update(['status' => 'published']);
    }
}
