<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyMedia extends Model
{
    use HasFactory;

    protected $table = 'agency_media';

    protected $fillable = ['agency_id', 'type', 'path', 'caption', 'sort_order'];

    protected function casts(): array
    {
        return [
            'type' => MediaType::class,
            'sort_order' => 'integer',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
