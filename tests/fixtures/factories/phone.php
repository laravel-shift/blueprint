<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Phone;
use Faker\Generator as Faker;

$factory->define(Phone::class, function (Faker $faker) {
    return [
        'label' => $faker->word,
        'user_id' => factory(\App\User::class),
        'phone_number' => $faker->phoneNumber,
        'type' => $faker->randomElement(["home","cell"]),
        'status' => $faker->randomElement(["archived","deleted"]),
        'foo_id' => $faker->randomDigitNotNull,
        'foo_type' => $faker->word,
        'tag' => $faker->regexify('[A-Za-z0-9]{3}'),
    ];
});
