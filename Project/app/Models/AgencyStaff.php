<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgencyStaff extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['agency_id', 'user_id', 'job_title', 'is_primary_contact'];

    protected function casts(): array
    {
        return ['is_primary_contact' => 'boolean'];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
