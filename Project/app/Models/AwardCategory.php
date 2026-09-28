<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardCategory extends Model
{
    protected $fillable = ['name', 'year', 'agency_category_id'];

    public function agencyCategory(): BelongsTo
    {
        return $this->belongsTo(AgencyCategory::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(Award::class);
    }
}
