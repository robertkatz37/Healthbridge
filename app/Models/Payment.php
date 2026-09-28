<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'amount', 'method', 'status', 'failure_reason',
        'refunded_amount', 'retry_count', 'stripe_payment_intent_id', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function scopeSucceeded($query)
    {
        return $query->where('status', PaymentStatus::Succeeded->value);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::Failed->value);
    }

    public function isFullyRefunded(): bool
    {
        return (float) $this->refunded_amount >= (float) $this->amount;
    }
}
