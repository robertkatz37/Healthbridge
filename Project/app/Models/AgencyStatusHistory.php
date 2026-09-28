<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'agency_status_history';

    protected $fillable = ['agency_id', 'from_status', 'to_status', 'reason', 'changed_by'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
