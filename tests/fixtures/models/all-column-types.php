<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AllType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bigInteger',
        'binary',
        'boolean',
        'char',
        'date',
        'dateTime',
        'dateTimeTz',
        'decimal',
        'double',
        'enum',
        'float',
        'geometry',
        'geometryCollection',
        'integer',
        'ipAddress',
        'json',
        'jsonb',
        'lineString',
        'longText',
        'macAddress',
        'mediumInteger',
        'mediumText',
        'morphs',
        'uuidMorphs',
        'multiLineString',
        'multiPoint',
        'multiPolygon',
        'nullableMorphs',
        'nullableUuidMorphs',
        'nullableTimestamps',
        'point',
        'polygon',
        'rememberToken',
        'set',
        'smallInteger',
        'string',
        'text',
        'time',
        'timeTz',
        'timestamp',
        'timestampTz',
        'tinyInteger',
        'unsignedBigInteger',
        'unsignedDecimal',
        'unsignedInteger',
        'unsignedMediumInteger',
        'unsignedSmallInteger',
        'unsignedTinyInteger',
        'uuid',
        'year',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'bigInteger' => 'integer',
        'boolean' => 'boolean',
        'decimal' => 'decimal',
        'double' => 'double',
        'float' => 'float',
        'json' => 'array',
        'mediumInteger' => 'integer',
        'smallInteger' => 'integer',
        'tinyInteger' => 'integer',
        'unsignedBigInteger' => 'integer',
        'unsignedDecimal' => 'decimal',
        'unsignedInteger' => 'integer',
        'unsignedMediumInteger' => 'integer',
        'unsignedSmallInteger' => 'integer',
        'unsignedTinyInteger' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'date',
        'dateTime',
        'dateTimeTz',
        'nullableTimestamps',
        'timestamp',
        'timestampTz',
    ];


    public function uuid()
    {
        return $this->belongsTo(\App\Uuid::class);
    }
}
