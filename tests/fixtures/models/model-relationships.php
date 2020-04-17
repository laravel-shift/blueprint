<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
    ];


    public function teams()
    {
        return $this->belongsToMany(\App\Team::class);
    }

    public function orders()
    {
        return $this->hasMany(\App\Order::class);
    }

    public function duration()
    {
        return $this->hasOne(\App\Duration::class);
    }

    public function transaction()
    {
        return $this->hasOne(\App\Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }
}
