<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id_banner
 * @property string $nama_banner
 * @property int $urutan
 * @property string $url_gambar
 * @property bool $shown
 */
class BannerModel extends Model
{
    use HasUuids;

    protected $table = 'm_banner';

    protected $primaryKey = 'id_banner';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id_banner',
        'nama_banner',
        'urutan',
        'url_gambar',
        'shown',
    ];

    protected $casts = [
        'shown' => 'boolean',
    ];

    protected $attributes = [
        'shown' => true,
    ];

    public $timestamps = false;
}
