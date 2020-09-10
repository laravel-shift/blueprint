<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Comment;
use Faker\Generator as Faker;

$factory->define(Comment::class, function (Faker $faker) {
    return [
        'post_id' => factory(\App\Post::class),
        'author_id' => factory(\App\User::class),
        'ccid' => function () {
            return factory(\App\Country::class)->create()->code;
        },
    ];
});
