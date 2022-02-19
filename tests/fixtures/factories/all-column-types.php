<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\AllType;

class AllTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AllType::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'bigInteger' => $this->faker->numberBetween(-100000, 100000),
            'binary' => $this->faker->sha256,
            'boolean' => $this->faker->boolean,
            'char' => $this->faker->randomLetter,
            'date' => $this->faker->date(),
            'dateTime' => $this->faker->dateTime(),
            'dateTimeTz' => $this->faker->dateTime(),
            'decimal' => $this->faker->randomFloat(0, 0, 9999999999.),
            'double' => $this->faker->randomFloat(0, 0, 9999999999.),
            'enum' => $this->faker->randomElement(["1","2","3"]),
            'float' => $this->faker->randomFloat(0, 0, 9999999999.),
            'fullText' => $this->faker->text,
            'geometry' => $this->faker->word,
            'geometryCollection' => $this->faker->word,
            'integer' => $this->faker->numberBetween(-10000, 10000),
            'ipAddress' => $this->faker->ipv4,
            'json' => '{}',
            'jsonb' => '{}',
            'lineString' => $this->faker->word,
            'longText' => $this->faker->text,
            'macAddress' => $this->faker->macAddress,
            'mediumInteger' => $this->faker->numberBetween(-10000, 10000),
            'mediumText' => $this->faker->text,
            'morphs_id' => $this->faker->randomDigitNotNull,
            'morphs_type' => $this->faker->word,
            'uuidMorphs' => $this->faker->word,
            'multiLineString' => $this->faker->word,
            'multiPoint' => $this->faker->word,
            'multiPolygon' => $this->faker->word,
            'point' => $this->faker->word,
            'polygon' => $this->faker->word,
            'rememberToken' => Str::random(10),
            'set' => $this->faker->randomElement(["1","2","3"]),
            'smallInteger' => $this->faker->numberBetween(-1000, 1000),
            'string' => $this->faker->word,
            'text' => $this->faker->text,
            'time' => $this->faker->time(),
            'timeTz' => $this->faker->time(),
            'timestamp' => $this->faker->dateTime(),
            'timestampTz' => $this->faker->dateTime(),
            'tinyInteger' => $this->faker->numberBetween(-8, 8),
            'unsignedBigInteger' => $this->faker->randomNumber(),
            'unsignedDecimal' => $this->faker->randomNumber(),
            'unsignedInteger' => $this->faker->randomNumber(),
            'unsignedMediumInteger' => $this->faker->randomNumber(),
            'unsignedSmallInteger' => $this->faker->randomNumber(),
            'unsignedTinyInteger' => $this->faker->randomDigitNotNull,
            'uuid' => $this->faker->uuid,
            'year' => $this->faker->year(),
        ];
    }
}
