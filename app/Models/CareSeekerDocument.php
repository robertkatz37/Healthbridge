<?php

namespace App\Models;

use App\Enums\CareSeekerDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareSeekerDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['care_seeker_id', 'uploaded_by', 'document_type', 'path', 'original_filename'];

    protected function casts(): array
    {
        return [
            'document_type' => CareSeekerDocumentType::class,
        ];
    }

    public function careSeeker(): BelongsTo
    {
        return $this->belongsTo(CareSeeker::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
