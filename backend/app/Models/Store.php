<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasUuid7;

    protected $fillable = [
        'alumni_profile_id',
        'name',
        'logo',
        'banner',
        'description',
        'kategori_usaha',
        'whatsapp',
        'kota',
        'tahun_berdiri',
        'status',
        'delivery_type',
        'fixed_delivery_fee',
    ];

    /**
     * Get the alumni profile that owns this store.
     */
    public function alumniProfile(): BelongsTo
    {
        return $this->belongsTo(AlumniProfile::class);
    }

    /**
     * Get the delivery fees for the store.
     */
    public function deliveryFees(): HasMany
    {
        return $this->hasMany(StoreDeliveryFee::class);
    }
}
