<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CampusHiringModel
 * * Mendefinisikan properti agar terbaca oleh PHPStan/IDE.
 *
 * * @property string $id_campus_hiring
 * @property string $id_perusahaan
 * @property string $id_admin_pembuat
 * @property string $nama_campus_hiring
 * @property string $jenis_lowongan
 * @property string|null $departemen
 * @property string|null $deskripsi
 * @property string|null $kualifikasi
 * @property string|null $benefit
 * @property array|null $kualifikasi_pendidikan
 * @property \Illuminate\Support\Carbon|null $batas_akhir
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class CampusHiringModel extends Model
{
    use HasUuids;

    // Menyesuaikan dengan TABLE_NAME di Sequelize
    protected $table = 't_campus_hiring';

    // Menyesuaikan primaryKey di Sequelize
    protected $primaryKey = 'id_campus_hiring';

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
        'nama_campus_hiring', // Diubah dari nama_lowongan
        'jenis_lowongan',
        'departemen',
        'deskripsi',
        'kualifikasi',
        'benefit',
        'kualifikasi_pendidikan',
        'batas_akhir',
        // 'link_pendaftaran' dihapus karena tidak ada di schema Sequelize
    ];

    protected $casts = [
        'kualifikasi_pendidikan' => 'array', // Sesuai dengan JSON handling di Sequelize
        'batas_akhir' => 'date',
    ];

    public $timestamps = true;
}
