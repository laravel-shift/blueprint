<?php

namespace App\Models;

use App\Models\Base\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Car extends Vehicle
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'color',
    ];
}
