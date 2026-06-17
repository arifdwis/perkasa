<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceImage extends Model
{
    use HasUuid7;

    protected $fillable = [
        'service_id',
        'image_path',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Get the service that owns this image.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
