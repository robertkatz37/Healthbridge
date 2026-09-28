<?php

namespace App\Models;

use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionRule extends Model
{
    protected $fillable = ['agency_id', 'rule_type', 'commission_type', 'amount', 'is_active'];

    protected function casts(): array
    {
        return [
            'commission_type' => CommissionType::class,
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function scopePlatformDefault($query)
    {
        return $query->whereNull('agency_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, CommissionType $type)
    {
        return $query->where('commission_type', $type->value);
    }

    /**
     * The commission amount this rule produces for a given base amount —
     * "flat" rule_type ignores the base and always returns the fixed
     * amount; "percentage" applies amount as a percentage of the base.
     */
    public function computeAmount(float $baseAmount = 0): float
    {
        return $this->rule_type === 'percentage'
            ? round($baseAmount * ((float) $this->amount / 100), 2)
            : (float) $this->amount;
    }
}
