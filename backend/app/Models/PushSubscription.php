<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    use HasUuid7;

    protected $fillable = [
        'user_id',
        'endpoint',
        'p256dh',
        'auth',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
