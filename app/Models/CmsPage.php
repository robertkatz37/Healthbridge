<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug', 'title', 'body', 'status', 'page_type', 'template',
        'author_id', 'published_at', 'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable');
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seo_metable');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class);
    }

    public function activeSections(): HasMany
    {
        return $this->hasMany(PageSection::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function revisions(): HasMany
    {
        // Ordered by id DESC rather than relying on created_at alone —
        // SQLite's timestamp precision is per-second, so two revisions
        // created within the same second (plausible for quick admin
        // edits, and for restore()'s own "archive current state, then
        // apply the restored one" sequence) can't be reliably
        // distinguished by timestamp. id is always monotonic.
        return $this->hasMany(PageRevision::class)->orderByDesc('id');
    }

    public function scopeVisible($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'published')
                ->orWhere(fn ($qq) => $qq->where('status', 'scheduled')->where('scheduled_at', '<=', now()));
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function isVisible(): bool
    {
        if ($this->status === 'published') {
            return true;
        }

        return $this->status === 'scheduled' && $this->scheduled_at !== null && $this->scheduled_at->isPast();
    }
}
