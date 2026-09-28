<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewCategoryRating extends Model
{
    protected $fillable = ['review_id', 'review_category_id', 'rating'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReviewCategory::class, 'review_category_id');
    }
}
