<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\AllType;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(AllType::class, function (Faker $faker) {
    return [
        'bigInteger' => $faker->numberBetween(-100000, 100000),
        'binary' => $faker->sha256,
        'boolean' => $faker->boolean,
        'char' => $faker->randomLetter,
        'date' => $faker->date(),
        'dateTime' => $faker->dateTime(),
        'dateTimeTz' => $faker->dateTime(),
        'decimal' => $faker->randomFloat(0, 0, 9999999999.),
        'double' => $faker->randomFloat(0, 0, 9999999999.),
        'enum' => $faker->randomElement(["1","2","3"]),
        'float' => $faker->randomFloat(0, 0, 9999999999.),
        'geometry' => $faker->word,
        'geometryCollection' => $faker->word,
        'integer' => $faker->numberBetween(-10000, 10000),
        'ipAddress' => $faker->ipv4,
        'json' => '{}',
        'jsonb' => '{}',
        'lineString' => $faker->word,
        'longText' => $faker->text,
        'macAddress' => $faker->macAddress,
        'mediumInteger' => $faker->numberBetween(-10000, 10000),
        'mediumText' => $faker->text,
        'morphs_id' => $faker->randomDigitNotNull,
        'morphs_type' => $faker->word,
        'uuidMorphs' => $faker->word,
        'multiLineString' => $faker->word,
        'multiPoint' => $faker->word,
        'multiPolygon' => $faker->word,
        'point' => $faker->word,
        'polygon' => $faker->word,
        'rememberToken' => Str::random(10),
        'set' => $faker->randomElement(["1","2","3"]),
        'smallInteger' => $faker->numberBetween(-1000, 1000),
        'string' => $faker->word,
        'text' => $faker->text,
        'time' => $faker->time(),
        'timeTz' => $faker->time(),
        'timestamp' => $faker->dateTime(),
        'timestampTz' => $faker->dateTime(),
        'tinyInteger' => $faker->numberBetween(-8, 8),
        'unsignedBigInteger' => $faker->randomNumber(),
        'unsignedDecimal' => $faker->randomNumber(),
        'unsignedInteger' => $faker->randomNumber(),
        'unsignedMediumInteger' => $faker->randomNumber(),
        'unsignedSmallInteger' => $faker->randomNumber(),
        'unsignedTinyInteger' => $faker->randomDigitNotNull,
        'uuid' => $faker->uuid,
        'year' => $faker->year(),
    ];
});
