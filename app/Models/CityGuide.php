<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class CityGuide extends Model
{
    protected $fillable = ['city_id', 'slug', 'intro_content', 'median_cost_data', 'status'];

    protected function casts(): array
    {
        return ['median_cost_data' => 'array'];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seo_metable');
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
