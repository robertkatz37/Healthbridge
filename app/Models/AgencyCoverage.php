<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyCoverage extends Model
{
    use HasFactory;

    protected $fillable = ['agency_id', 'city', 'state', 'zip_code', 'radius_miles'];

    protected function casts(): array
    {
        return ['radius_miles' => 'integer'];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
