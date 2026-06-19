<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Service extends Model
{
    use HasUuid7;

    protected $fillable = [
        'store_id',
        'service_category_id',
        'name',
        'slug',
        'description',
        'price_from',
        'lokasi_layanan',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'price_from' => 'decimal:2',
        'is_featured' => 'boolean',
    ];

    protected $appends = [
        'average_rating',
        'reviews_count',
    ];

    /**
     * Get the store that offers this service.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the service category this service belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    /**
     * Get all images (portfolio) for this service.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ServiceImage::class);
    }

    /**
     * Get the primary (cover) image of this service.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ServiceImage::class)->where('is_primary', true);
    }

    /**
     * Get all reviews for the service.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Accessor for average rating.
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
