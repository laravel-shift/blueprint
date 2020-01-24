<?php

namespace Some\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property integer $post_id
 * @property integer $author_id
 */
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
        return $this->belongsTo(\Some\App\Models\Post::class);
    }

    public function author()
    {
        return $this->belongsTo(\Some\App\Models\User::class);
    }
}
