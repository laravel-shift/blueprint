<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CertificateType extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];


    public function certificates()
    {
        return $this->hasMany(\App\Certificate::class);
    }
}
