<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class City extends Model
{
    protected $fillable = [
        'state_id', 'name', 'slug', 'lat', 'lng', 'population', 'is_metro_hub', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_metro_hub' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function guide(): HasOne
    {
        return $this->hasOne(CityGuide::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMetroHub($query)
    {
        return $query->where('is_metro_hub', true);
    }

    /**
     * `slug` is globally unique at the DB level (e.g. "los-angeles-ca")
     * so two states can't collide, but that suffix makes an ugly public
     * URL. State+City pages are always looked up scoped to a specific
     * state already, so a per-state-scoped plain slug (no suffix) is
     * all a clean URL needs — computed here rather than stored, since
     * it's a pure transform of `name`.
     */
    public function getCleanSlugAttribute(): string
    {
        return \Illuminate\Support\Str::slug($this->name);
    }
}
