<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Post;
use Faker\Generator as Faker;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(4),
        'author_id' => factory(\App\Author::class),
        'author_bio' => $faker->text,
        'content' => $faker->paragraphs(3, true),
        'published_at' => $faker->dateTime(),
        'word_count' => $faker->randomNumber(),
    ];
});
