<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyAdminNote extends Model
{
    use HasFactory;

    protected $fillable = ['agency_id', 'user_id', 'note'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
