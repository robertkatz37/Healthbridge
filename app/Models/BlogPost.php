<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'blog_category_id', 'author_id', 'slug', 'title', 'excerpt',
        'body', 'featured_image', 'status', 'is_featured', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'is_featured' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class)->whereNull('parent_id')->approved()->latest();
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seo_metable');
    }

    public function scopeVisible($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'published')
                ->orWhere(fn ($qq) => $qq->where('status', 'scheduled')->where('published_at', '<=', now()));
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function isVisible(): bool
    {
        if ($this->status === 'published') {
            return true;
        }

        return $this->status === 'scheduled' && $this->published_at !== null && $this->published_at->isPast();
    }

    public function getReadingTimeMinutesAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->body));

        return max(1, (int) ceil($wordCount / 200));
    }

    public function relatedPosts(int $limit = 3)
    {
        $tagIds = $this->tags->pluck('id');

        return static::visible()
            ->where('id', '!=', $this->id)
            ->where(function ($q) use ($tagIds) {
                $q->where('blog_category_id', $this->blog_category_id);
                if ($tagIds->isNotEmpty()) {
                    $q->orWhereHas('tags', fn ($qq) => $qq->whereIn('blog_tags.id', $tagIds));
                }
            })
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }
}
