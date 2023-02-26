<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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

    public function stars(): MorphOne
    {
        return $this->morphOne(\Other\Package\Order::class, 'starable');
    }

    public function durations(): MorphMany
    {
        return $this->morphMany(\Other\Package\Duration::class, 'durationable');
    }

    public function lines(): MorphMany
    {
        return $this->morphMany(\App\MyCustom\Folder\Transaction::class, 'lineable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
