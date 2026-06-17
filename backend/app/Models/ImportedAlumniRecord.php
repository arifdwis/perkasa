<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Model;

class ImportedAlumniRecord extends Model
{
    use HasUuid7;

    protected $fillable = [
        'nim',
        'name',
        'program_studi',
        'tahun_masuk',
        'tahun_lulus',
        'email',
        'whatsapp',
    ];
}
