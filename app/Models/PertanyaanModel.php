<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $kode_pertanyaan
 * @property string $tracer_id
 * @property string $teks_pertanyaan
 * @property string $tipe_pertanyaan
 * @property array|null $opsi_jawaban
 * @property string|null $sumber_opsi
 * @property string|null $bergantung_pada_id
 * @property array|null $bergantung_pada_nilai
 * @property string|null $bergantung_pada_kode
 * @property bool $wajib_diisi
 * @property string|null $kategori_pertanyaan
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class PertanyaanModel extends Model
{
    protected $table = 't_pertanyaan';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'kode_pertanyaan',
        'tracer_id',
        'teks_pertanyaan',
        'tipe_pertanyaan',
        'opsi_jawaban',
        'sumber_opsi',
        'bergantung_pada_id',
        'bergantung_pada_kode', // TAMBAH INI
        'bergantung_pada_nilai',
        'wajib_diisi',
        'kategori_pertanyaan',
    ];

    protected $casts = [
        'opsi_jawaban' => 'array',
        'bergantung_pada_nilai' => 'array',
        'wajib_diisi' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
