<?php

namespace App\Models;

use App\Interfaces\IDriveable;
use App\Interfaces\IRidable;
use App\Models\Base\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Car extends Vehicle implements IDriveable, IRidable
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
