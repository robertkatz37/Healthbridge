<?php

namespace App\Models;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = ['referral_id', 'commission_rule_id', 'commission_type', 'amount', 'status', 'beneficiary_type', 'beneficiary_id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => CommissionStatus::class,
            'commission_type' => CommissionType::class,
        ];
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Who this commission is owed to — a User (advisor) for
     * advisor_commission rows; null for the platform-fee-from-agency
     * case (referral_fee), where the "beneficiary" is simply the
     * platform itself and there's no separate row to point at.
     */
    public function beneficiary(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeDue($query)
    {
        return $query->where('status', CommissionStatus::Due->value);
    }

    public function scopeOfType($query, CommissionType $type)
    {
        return $query->where('commission_type', $type->value);
    }

    public function scopeForBeneficiary($query, Model $beneficiary)
    {
        return $query->where('beneficiary_type', $beneficiary::class)->where('beneficiary_id', $beneficiary->id);
    }
}
