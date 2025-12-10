<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $kode_prodi
 * @property string $nama_prodi
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class MProdiModel extends Model
{
    protected $table = 'm_prodi';

    protected $fillable = [
        'id',
        'kode_prodi',
        'nama_prodi',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
