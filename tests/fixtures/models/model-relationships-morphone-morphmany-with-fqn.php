<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flag extends Model
{
    use HasFactory;

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

    public function stars()
    {
        return $this->morphOne(\Other\Package\Order::class, 'starable');
    }

    public function durations()
    {
        return $this->morphMany(\Other\Package\Duration::class, 'durationable');
    }

    public function lines()
    {
        return $this->morphMany(\App\MyCustom\Folder\Transaction::class, 'lineable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
