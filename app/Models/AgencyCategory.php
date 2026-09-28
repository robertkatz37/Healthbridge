<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgencyCategory extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'slug', 'description', 'icon', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function agencies(): HasMany
    {
        return $this->hasMany(Agency::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ServiceCatalog::class);
    }

    public function guide(): HasOne
    {
        return $this->hasOne(ServiceGuide::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
