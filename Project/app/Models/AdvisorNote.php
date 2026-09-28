<?php

namespace App\Models;

use App\Enums\AdvisorNoteType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorNote extends Model
{
    use HasFactory;

    protected $fillable = ['advisor_id', 'family_id', 'lead_id', 'note_type', 'is_internal', 'content'];

    protected function casts(): array
    {
        return [
            'note_type' => AdvisorNoteType::class,
            'is_internal' => 'boolean',
        ];
    }

    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_internal', false);
    }
}
