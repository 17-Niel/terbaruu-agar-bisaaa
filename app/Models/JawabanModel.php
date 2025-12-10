<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $responden_id
 * @property string $pertanyaan_id
 * @property string $nilai_jawaban
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class JawabanModel extends Model
{
    protected $table = 't_jawaban';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'responden_id',
        'pertanyaan_id',
        'nilai_jawaban',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
