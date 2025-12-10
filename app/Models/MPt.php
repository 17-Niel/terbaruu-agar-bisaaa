<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MPt extends Model
{
    protected $table = 'm_pt';

    // Default auto-increment
    protected $fillable =
        ['kode_pt',
            'nama_pt'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
