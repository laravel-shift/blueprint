<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'post_id',
        'author_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'post_id' => 'integer',
        'author_id' => 'integer',
    ];


    public function post()
    {
        return $this->belongsTo(\App\Post::class);
    }

    public function author()
    {
        return $this->belongsTo(\App\User::class);
    }
}
