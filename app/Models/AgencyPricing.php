<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyPricing extends Model
{
    use HasFactory;

    protected $table = 'agency_pricing';

    protected $fillable = ['agency_id', 'room_type', 'care_level', 'monthly_price'];

    protected function casts(): array
    {
        return ['monthly_price' => 'decimal:2'];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
