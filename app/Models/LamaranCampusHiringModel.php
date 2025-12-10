<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Helper untuk PHPStan/Intellisense agar tidak error "Access to undefined property".
 *
 * * @property string $id_lamaran
 * @property string $id_campus_hiring
 * @property string|int $user_id
 * @property string $nama_pelamar
 * @property string $url_cv
 * @property \Illuminate\Support\Carbon|null $tanggal_lamaran <-- Karena Anda set CREATED_AT = 'tanggal_lamaran'
 */
class LamaranCampusHiringModel extends Model
{
    use HasFactory;

    protected $table = 't_lamaran_campus_hiring';

    protected $primaryKey = 'id_lamaran';

    public $incrementing = false;

    protected $keyType = 'string';

    // Mendefinisikan kolom custom untuk timestamps
    const CREATED_AT = 'tanggal_lamaran';

    const UPDATED_AT = null;

    protected $fillable = [
        'id_lamaran',
        'id_campus_hiring',
        'user_id',
        'nama_pelamar',
        'url_cv',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id_lamaran)) {
                $model->id_lamaran = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
