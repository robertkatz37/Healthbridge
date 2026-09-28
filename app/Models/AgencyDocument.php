<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgencyDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id', 'document_type', 'path', 'original_filename',
        'expires_at', 'verified_at', 'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'expires_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }
}
