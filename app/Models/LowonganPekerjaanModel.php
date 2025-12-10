<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id_lowongan
 * @property string $id_perusahaan
 * @property string $id_admin_pembuat
 * @property string $jenis_lowongan
 * @property string $nama_lowongan
 * @property string $departemen
 * @property string $deskripsi
 * @property string $kualifikasi
 * @property string $benefit
 * @property array $kualifikasi_pendidikan
 * @property string $link_pendaftaran
 * @property \Illuminate\Support\Carbon $batas_akhir
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class LowonganPekerjaanModel extends Model
{
    use HasUuids;

    protected $table = 't_lowongan_pekerjaan';

    protected $primaryKey = 'id_lowongan';

    protected $keyType = 'string';

    public $incrementing = false;

    // --- DEKLARASI STATIS (ENUM) ---
    const TYPE_FULL_TIME = 'Full Time';

    const TYPE_PART_TIME = 'Part Time';

    const TYPE_CONTRACT = 'Contract/Project-Based';

    /**
     * Helper untuk mengambil daftar opsi jenis lowongan.
     * Digunakan untuk validasi di Controller dan Dropdown di Frontend.
     */
    public static function getJenisOptions()
    {
        return [
            self::TYPE_FULL_TIME,
            self::TYPE_PART_TIME,
            self::TYPE_CONTRACT,
        ];
    }

    protected $fillable = [
        'id_perusahaan',
        'id_admin_pembuat',
        'jenis_lowongan',
        'nama_lowongan',
        'departemen',
        'deskripsi',
        'kualifikasi',
        'benefit',
        'kualifikasi_pendidikan',
        'link_pendaftaran',
        'batas_akhir',
    ];

    protected $casts = [
        'kualifikasi_pendidikan' => 'array',
        'batas_akhir' => 'date',
    ];

    public $timestamps = true;
}
