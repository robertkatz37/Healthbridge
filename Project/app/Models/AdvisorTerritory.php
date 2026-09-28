<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorTerritory extends Model
{
    use HasFactory;

    protected $fillable = ['advisor_id', 'city', 'state', 'radius_miles'];

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    public function scopeInCity($query, string $city, string $state)
    {
        return $query->where('city', $city)->where('state', $state);
    }
}
