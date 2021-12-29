<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recurrency extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'product_id',
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
        return $this->belongsToMany(\Some\Package\Team::class);
    }

    public function orders()
    {
        return $this->hasMany(\Other\Package\Order::class);
    }

    public function duration()
    {
        return $this->hasOne(\Other\Package\Duration::class);
    }

    public function transaction()
    {
        return $this->hasOne(\App\MyCustom\Folder\Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
