<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralNote extends Model
{
    use HasFactory;

    protected $fillable = ['referral_id', 'author_id', 'author_type', 'visible_to_agency', 'visible_to_family', 'content'];

    protected function casts(): array
    {
        return [
            'visible_to_agency' => 'boolean',
            'visible_to_family' => 'boolean',
        ];
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopeVisibleToAgency($query)
    {
        return $query->where(function ($q) {
            $q->where('author_type', 'agency')->orWhere('visible_to_agency', true);
        });
    }

    public function scopeVisibleToFamily($query)
    {
        return $query->where('visible_to_family', true);
    }
}
