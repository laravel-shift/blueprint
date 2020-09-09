<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'owner',
        'manager',
        'options',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'owner' => 'integer',
        'manager' => 'integer',
        'options' => 'array',
    ];


    public function owner()
    {
        return $this->belongsTo(\App\Owner::class);
    }

    public function manager()
    {
        return $this->belongsTo(\App\User::class);
    }
}
