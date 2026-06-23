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
        'kecamatan',
        'kelurahan',
        'latitude',
        'longitude',
        'tahun_berdiri',
        'status',
        'delivery_type',
        'fixed_delivery_fee',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'fixed_delivery_fee' => 'decimal:2',
    ];

    protected $appends = [
        'average_rating',
        'reviews_count',
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

    /**
     * Get all reviews received by this store.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get all orders for this store.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Accessor for average store rating.
     */
    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    /**
     * Accessor for total reviews count.
     */
    public function getReviewsCountAttribute()
    {
        return $this->reviews()->count();
    }
}
