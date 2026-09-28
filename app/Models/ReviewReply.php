<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewReply extends Model
{
    protected $fillable = ['review_id', 'user_id', 'body', 'edited_at'];

    protected function casts(): array
    {
        return ['edited_at' => 'datetime'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ReviewReplyRevision::class)->latest();
    }

    /**
     * "Edited responses should maintain history" — archives the current
     * body as a revision before overwriting it, then updates in place.
     */
    public function updateBodyWithHistory(string $newBody): void
    {
        $this->revisions()->create(['body' => $this->body]);
        $this->update(['body' => $newBody, 'edited_at' => now()]);
    }
}
