<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasUuid7;

    protected $fillable = [
        'code', 'type', 'value', 'min_order', 'max_discount',
        'usage_limit', 'used_count', 'valid_from', 'valid_until',
        'store_id', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        if ($this->valid_from && now()->lt($this->valid_from)) return false;
        if ($this->valid_until && now()->gt($this->valid_until)) return false;
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < $this->min_order) return 0;

        if ($this->type === 'percentage') {
            $discount = $subtotal * ($this->value / 100);
            if ($this->max_discount) {
                $discount = min($discount, (float) $this->max_discount);
            }
            return $discount;
        }

        return min((float) $this->value, $subtotal);
    }
}
