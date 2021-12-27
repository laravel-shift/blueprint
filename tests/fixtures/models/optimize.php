<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tiny
 * @property int $small
 * @property int $medium
 * @property int $int
 * @property float $dec
 * @property int $big
 * @property int $foo_id
 * @property string $foo_type
 * @property string $bar_id
 * @property string $bar_type
 * @property int|null $baz_id
 * @property string|null $baz_type
 * @property string $foobar_id
 * @property string $foobar_type
 * @property string|null $foobarbaz_id
 * @property string|null $foobarbaz_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Optimize extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tiny',
        'small',
        'medium',
        'int',
        'dec',
        'big',
        'foo',
        'bar',
        'baz',
        'foobar',
        'foobarbaz',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'tiny' => 'integer',
        'small' => 'integer',
        'medium' => 'integer',
        'dec' => 'decimal:2',
        'big' => 'integer',
    ];
}
