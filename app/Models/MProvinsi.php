<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MProvinsi extends Model
{
    protected $table = 'm_provinsi';

    // Default auto-increment
    protected $fillable =
        ['id',
            'kode_provinsi',
            'nama_provinsi'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
