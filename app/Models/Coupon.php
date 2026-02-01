<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'amount',
        'minimum_amount',
        'usage_limit',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Check if coupon is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < $this->minimum_amount) {
            return 0;
        }

        return match ($this->type) {
            'Percentage' => ($subtotal * $this->amount) / 100,
            'Fixed Amount' => $this->amount,
            'Free Shipping' => 0, // Handled separately
            default => 0,
        };
    }

    /**
     * Scope for active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
