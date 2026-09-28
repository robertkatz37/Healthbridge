<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id', 'family_id', 'reviewer_name', 'reviewer_relationship',
        'title', 'body', 'overall_rating', 'is_verified', 'verified_by',
        'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'status' => ReviewStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function categoryRatings(): HasMany
    {
        return $this->hasMany(ReviewCategoryRating::class);
    }

    public function reply(): HasOne
    {
        return $this->hasOne(ReviewReply::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeRecentlyPublished($query, int $months = 24)
    {
        return $query->where('published_at', '>=', now()->subMonths($months));
    }
}
