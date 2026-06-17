<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreDeliveryFee extends Model
{
    use HasUuid7;

    protected $fillable = [
        'store_id',
        'wilayah',
        'fee',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
    ];

    /**
     * Get the store that owns this delivery fee.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
