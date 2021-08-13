<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;
use Some\App\Models\Post;

$factory->define(Post::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(4),
        'author_id' => factory(\Some\App\Models\Author::class),
        'author_bio' => $faker->text,
        'content' => $faker->paragraphs(3, true),
        'published_at' => $faker->dateTime(),
        'updated_at' => $faker->dateTime(),
        'word_count' => $faker->randomNumber(),
    ];
});
