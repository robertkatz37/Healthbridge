<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    protected $fillable = ['agency_id', 'stripe_payment_method_id', 'brand', 'last_four', 'exp_month', 'exp_year', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return ucfirst($this->brand ?? 'Card') . ' •••• ' . $this->last_four;
    }

    public function isExpired(): bool
    {
        if (!$this->exp_month || !$this->exp_year) {
            return false;
        }

        return now()->startOfMonth()->greaterThan(now()->setDate($this->exp_year, $this->exp_month, 1)->endOfMonth());
    }
}
