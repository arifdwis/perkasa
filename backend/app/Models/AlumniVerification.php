<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniVerification extends Model
{
    use HasUuid7;

    protected $fillable = [
        'alumni_profile_id',
        'admin_user_id',
        'action',
        'reason',
    ];

    /**
     * Get the associated profile being verified.
     */
    public function alumniProfile(): BelongsTo
    {
        return $this->belongsTo(AlumniProfile::class);
    }

    /**
     * Get the admin who verified the profile.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
