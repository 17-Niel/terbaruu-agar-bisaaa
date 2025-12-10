<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisLowonganModel extends Model
{
    protected $table = 'm_jenis_lowongan';

    protected $primaryKey = 'id_jenis';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_jenis',
        'nama_jenis',
    ];

    public $timestamps = false;
}
