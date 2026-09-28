<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReplyRevision extends Model
{
    protected $fillable = ['review_reply_id', 'body'];

    public function reply(): BelongsTo
    {
        return $this->belongsTo(ReviewReply::class, 'review_reply_id');
    }
}
