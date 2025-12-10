<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id_perusahaan
 * @property string $nama
 * @property string $lokasi
 * @property string $website
 * @property string $industri
 * @property string $deskripsi
 * @property string|null $url_logo
 * @property int $total_jobs <-- PENTING: Untuk menghilangkan error di Controller
 */
class PerusahaanModel extends Model
{
    use HasFactory;

    protected $table = 'm_perusahaan';

    protected $primaryKey = 'id_perusahaan';

    public $incrementing = false;

    protected $keyType = 'string';

    // [PERBAIKAN TIMESTAMP]
    // 1. Aktifkan timestamps agar created_at terisi otomatis
    public $timestamps = true;

    // 2. Beritahu Laravel bahwa tabel ini TIDAK punya kolom updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'id_perusahaan',
        'nama',
        'lokasi',
        'website',
        'industri',
        'deskripsi',
        'url_logo',
    ];
}
