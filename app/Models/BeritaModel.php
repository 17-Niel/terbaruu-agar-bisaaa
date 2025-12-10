<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id_berita
 * @property string $judul
 * @property string $status
 * @property string $isi
 * @property string|null $gambar
 * @property \Illuminate\Support\Carbon $tanggal
 * @property string $author
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BeritaModel extends Model
{
    use HasFactory;

    protected $table = 'm_berita';

    protected $primaryKey = 'id_berita';

    protected $fillable = [
        'judul',
        'status',
        'isi',
        'gambar',
        'tanggal',
        'author',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
