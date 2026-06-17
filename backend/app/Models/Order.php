<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuid7;

    protected $fillable = [
        'order_number',
        'user_id',
        'store_id',
        'nama_penerima',
        'telepon_penerima',
        'alamat_penerima',
        'wilayah_antar',
        'subtotal',
        'biaya_antar',
        'total',
        'payment_method',
        'status',
        'catatan',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'biaya_antar' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the user that placed the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the store that sells the items in this order.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the items in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
