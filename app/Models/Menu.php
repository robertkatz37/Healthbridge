<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = ['slug', 'name'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function topLevelItems(): HasMany
    {
        return $this->items()->whereNull('parent_id')->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'), 'page'])
            ->orderBy('sort_order');
    }
}
