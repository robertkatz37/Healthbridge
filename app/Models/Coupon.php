<?php

namespace App\Models;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'stripe_coupon_id', 'type', 'value', 'applies_to',
        'plan_id', 'max_uses', 'times_used', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'applies_to' => CouponAppliesTo::class,
            'value' => 'decimal:2',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function hasUsesRemaining(): bool
    {
        return $this->max_uses === null || $this->times_used < $this->max_uses;
    }

    public function appliesToplan(?Plan $plan): bool
    {
        return $this->plan_id === null || $this->plan_id === $plan?->id;
    }

    public function isRedeemable(?Plan $plan = null): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if (!$this->hasUsesRemaining()) {
            return false;
        }
        if ($plan && !$this->appliesToplan($plan)) {
            return false;
        }

        return true;
    }

    /**
     * The discount amount this coupon takes off a given subtotal —
     * percentage coupons are capped so they can never discount below
     * zero, fixed coupons are capped at the subtotal itself.
     */
    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === CouponType::Percentage
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, $subtotal), 2);
    }
}
