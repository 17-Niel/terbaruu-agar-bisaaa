<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $akses
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property mixed $user
 * @property array $data_akses
 */
class HakAksesModel extends Model
{
    protected $table = 'm_hak_akses';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'akses',
    ];

    public $timestamps = true;
}
