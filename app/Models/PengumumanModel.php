<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $judul
 * @property string $isi
 * @property \Illuminate\Support\Carbon|null $expired_date
 * @property string|null $gambar_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PengumumanModel extends Model
{
    use HasFactory;

    protected $table = 'm_pengumuman';

    protected $fillable = [
        'judul',
        'isi',
        'expired_date',
        'gambar_path',
    ];

    // Tambahkan casting untuk dates
    protected $casts = [
        'expired_date' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
