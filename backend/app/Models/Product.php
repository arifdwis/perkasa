<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasUuid7;

    protected $fillable = [
        'store_id',
        'product_category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'status',
        'is_featured',
        'product_type',
        'pre_order_deadline',
        'pre_order_estimated_ship',
        'pre_order_min_qty',
        'pre_order_max_qty',
        'is_flash_sale',
        'flash_sale_price',
        'flash_sale_start',
        'flash_sale_end',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_featured' => 'boolean',
        'is_flash_sale' => 'boolean',
        'flash_sale_price' => 'decimal:2',
        'flash_sale_start' => 'datetime',
        'flash_sale_end' => 'datetime',
        'pre_order_deadline' => 'datetime',
        'pre_order_estimated_ship' => 'date',
        'pre_order_min_qty' => 'integer',
        'pre_order_max_qty' => 'integer',
    ];

    protected $appends = [
        'current_price',
        'total_stock',
        'min_variant_price',
        'is_flash_sale_active',
        'average_rating',
        'reviews_count',
        'primary_image_url',
    ];

    public function getTotalStockAttribute(): int
    {
        if ($this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            return (int) $this->variants->sum('stock');
        }
        if ($this->variants()->exists()) {
            return (int) $this->variants()->sum('stock');
        }
        return (int) $this->stock;
    }

    public function getMinVariantPriceAttribute(): ?float
    {
        if ($this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            return (float) $this->variants->min('price');
        }
        if ($this->variants()->exists()) {
            return (float) $this->variants()->min('price');
        }
        return null;
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        if ($this->relationLoaded('primaryImage') && $this->primaryImage) {
            return $this->primaryImage->image_path;
        }
        if ($this->relationLoaded('images') && $this->images->isNotEmpty()) {
            $primary = $this->images->firstWhere('is_primary', true);
            if ($primary) return $primary->image_path;
            return $this->images->first()->image_path;
        }
        if ($this->relationLoaded('variants')) {
            foreach ($this->variants as $variant) {
                if ($variant->relationLoaded('images') && $variant->images->isNotEmpty()) {
                    return \Illuminate\Support\Facades\Storage::disk('public')->url($variant->images->first()->image_path);
                }
            }
        }
        $primary = $this->primaryImage()->first();
        if ($primary) return $primary->image_path;
        return null;
    }

    public function getCurrentPriceAttribute(): float
    {
        if ($this->is_flash_sale_active) {
            return (float) $this->flash_sale_price;
        }
        if ($this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            return (float) $this->variants->min('price');
        }
        if ($this->variants()->exists()) {
            return (float) $this->variants()->min('price');
        }
        return (float) $this->price;
    }

    public function getIsFlashSaleActiveAttribute(): bool
    {
        if (! $this->is_flash_sale || ! $this->flash_sale_price) {
            return false;
        }
        $now = now();
        if ($this->flash_sale_start && $now->lt($this->flash_sale_start)) return false;
        if ($this->flash_sale_end && $now->gt($this->flash_sale_end)) return false;
        return true;
    }

    /**
     * Get the store that owns the product.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the category that the product belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * Get all images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the primary image of the product.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function variantImages(): HasMany
    {
        return $this->hasManyThrough(ProductVariantImage::class, ProductVariant::class);
    }

    /**
     * Get all reviews for the product.
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get all order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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
