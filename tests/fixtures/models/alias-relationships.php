<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salesman extends Model
{
    use HasFactory;

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

    public function lead()
    {
        return $this->hasOne(User::class);
    }

    public function methodNames()
    {
        return $this->hasMany(ClassName::class);
    }

    public function methodName()
    {
        return $this->belongsTo(ClassName::class);
    }
}
