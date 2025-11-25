<?php

namespace App\Models;

use App\Models\Base\Vehicle;
use App\Traits\HasEngine;
use App\Traits\HasWheels;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Car extends Vehicle
{
    use HasEngine, HasFactory, HasWheels;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'color',
    ];
}
