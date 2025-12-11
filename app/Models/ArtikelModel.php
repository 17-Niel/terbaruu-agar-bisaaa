<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $title
 * @property string $content
 * @property string $category
 * @property bool|int $is_published
 * @property string|int $user_id
 * @property string|null $attachment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ArtikelModel extends Model
{
    use HasFactory;

    protected $table = 'artikels';

    // Primary key adalah 'id' yang berupa UUID string
    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'content',
        'category',
        'is_published',
        'user_id',
        'attachment',
    ];

    protected static function boot()
    {
        parent::boot();

        // generate UUID jika belum ada
        static::creating(function ($model) {
            if (! $model->id) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
