<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyNote extends Model
{
    use HasFactory;

    protected $fillable = ['family_id', 'care_seeker_id', 'note'];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function careSeeker(): BelongsTo
    {
        return $this->belongsTo(CareSeeker::class);
    }
}
