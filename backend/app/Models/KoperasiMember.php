<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Staging table for koperasi membership registrations.
 *
 * Rows land here at step 1 (personal data, no credentials yet) and are promoted
 * into users + alumni_profiles at step 3. Column names mirror those two tables
 * so the eventual merge is a straight mapping — see koperasi.md section 5.
 */
class KoperasiMember extends Model
{
    use HasUuid7;

    protected $fillable = [
        'name',
        'email',
        'nim',
        'program_studi',
        'tahun_masuk',
        'tahun_lulus',
        'whatsapp',
        'status',
        'catatan_admin',
        'approved_at',
        'approved_by',
        'user_id',
        'migrated_at',
        'token_aktivasi',
        'token_expires_at',
        'token_diterbitkan_at',
    ];

    protected $casts = [
        'tahun_masuk' => 'integer',
        'tahun_lulus' => 'integer',
        'approved_at' => 'datetime',
        'migrated_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'token_diterbitkan_at' => 'datetime',
    ];

    /**
     * The activation token is never sent to the public activation page — only
     * to the admin who issues the link.
     */
    protected $hidden = ['token_aktivasi'];

    /**
     * The account created from this registration, once activated.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who approved or rejected this membership.
     *
     * Named `admin` rather than `approvedBy` on purpose: the latter serialises
     * to the same JSON key as the `approved_by` column and would shadow it.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * A registration still waiting for its owner to create an account.
     */
    public function isActivated(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Whether the admin-issued activation link is still usable.
     */
    public function hasLiveToken(): bool
    {
        return $this->token_aktivasi !== null
            && ! $this->isActivated()
            && ($this->token_expires_at === null || $this->token_expires_at->isFuture());
    }
}
