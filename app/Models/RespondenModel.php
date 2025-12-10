<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tracer_id
 * @property string $prodi_id
 * @property string $nama_responden
 * @property string $nim
 * @property int $tahun_lulus
 * @property string|null $email_responden
 * @property string|null $no_hp
 * @property bool $status_selesai
 * @property string|null $tanggal_submit
 * @property int|null $responden_selesai
 * @property int|null $total_responden
 * @property string|null $nama_prodi
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class RespondenModel extends Model
{
    protected $table = 't_responden';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'tracer_id',
        'nim',
        'pt_id',
        'prodi_id',
        'nama_responden',
        'no_hp',
        'email_responden',
        'tahun_lulus',
        'nik',
        'npwp',
        'status_selesai',
    ];

    protected $casts = [
        'status_selesai' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
