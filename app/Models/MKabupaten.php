<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MKabupaten extends Model
{
    protected $table = 'm_kabupaten';

    // Default auto-increment
    protected $fillable =
        ['id',
            'kode_kabupaten',
            'nama_kabupaten',
            'provinsi_id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
