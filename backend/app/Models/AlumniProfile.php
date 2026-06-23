<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AlumniProfile extends Model
{
    use HasUuid7;

    protected $fillable = [
        'user_id',
        'nim',
        'program_studi',
        'tahun_masuk',
        'tahun_lulus',
        'whatsapp',
        'domisili',
        'latitude',
        'longitude',
        'foto_profil',
        'status_verifikasi',
        'badge_verified',
    ];

    protected $casts = [
        'badge_verified' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the store owned by the alumni profile.
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }
}
