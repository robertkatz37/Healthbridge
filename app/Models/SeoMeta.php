<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'seo_metable_type', 'seo_metable_id', 'meta_title', 'meta_description',
        'og_image', 'canonical_url', 'json_ld',
    ];

    protected function casts(): array
    {
        return ['json_ld' => 'array'];
    }

    public function seoMetable(): MorphTo
    {
        return $this->morphTo();
    }
}
