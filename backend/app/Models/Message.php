<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasUuid7;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'type',
        'text',
        'product_id',
    ];

    protected $appends = [
        'is_mine',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getIsMineAttribute(): ?bool
    {
        if (! auth()->check()) {
            return null;
        }

        return $this->user_id === auth()->id();
    }
}
