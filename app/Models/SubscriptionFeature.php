<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionFeature extends Model
{
    protected $fillable = ['code', 'name', 'value_type'];

    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }
}
