<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralStatusHistory extends Model
{
    protected $table = 'referral_status_history';

    protected $fillable = ['referral_id', 'from_status', 'to_status', 'reason', 'changed_by'];

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
