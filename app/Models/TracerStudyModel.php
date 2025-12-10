<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $nama_tracer
 * @property string $tahun
 * @property string|null $tanggal_berakhir
 * @property string|null $deskripsi
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class TracerStudyModel extends Model
{
    use SoftDeletes;

    protected $table = 't_tracer_study';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'nama_tracer',
        'tahun',
        'tanggal_berakhir',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'tanggal_berakhir' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime', // TAMBAHKAN INI
        'is_active' => 'boolean',
    ];
}
