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
     */
    public function definition(): array
    {
        return [
            'bigInteger' => fake()->numberBetween(-100000, 100000),
            'binary' => fake()->sha256(),
            'boolean' => fake()->boolean(),
            'char' => fake()->randomLetter(),
            'date' => fake()->date(),
            'dateTime' => fake()->dateTime(),
            'dateTimeTz' => fake()->dateTime(),
            'decimal' => fake()->randomFloat(0, 0, 9999999999.),
            'double' => fake()->randomFloat(0, 0, 9999999999.),
            'enum' => fake()->randomElement(["1","2","3"]),
            'float' => fake()->randomFloat(0, 0, 9999999999.),
            'fullText' => fake()->text(),
            'geometry' => fake()->word(),
            'geometryCollection' => fake()->word(),
            'integer' => fake()->numberBetween(-10000, 10000),
            'ipAddress' => fake()->ipv4(),
            'json' => '{}',
            'jsonb' => '{}',
            'lineString' => fake()->word(),
            'longText' => fake()->text(),
            'macAddress' => fake()->macAddress(),
            'mediumInteger' => fake()->numberBetween(-10000, 10000),
            'mediumText' => fake()->text(),
            'morphs_id' => fake()->randomDigitNotNull(),
            'morphs_type' => fake()->word(),
            'ulidMorphs' => fake()->word(),
            'uuidMorphs' => fake()->word(),
            'multiLineString' => fake()->word(),
            'multiPoint' => fake()->word(),
            'multiPolygon' => fake()->word(),
            'point' => fake()->word(),
            'polygon' => fake()->word(),
            'rememberToken' => Str::random(10),
            'set' => fake()->randomElement(["1","2","3"]),
            'smallInteger' => fake()->numberBetween(-1000, 1000),
            'string' => fake()->word(),
            'text' => fake()->text(),
            'time' => fake()->time(),
            'timeTz' => fake()->time(),
            'timestamp' => fake()->dateTime(),
            'timestampTz' => fake()->dateTime(),
            'tinyInteger' => fake()->numberBetween(-8, 8),
            'unsignedBigInteger' => fake()->randomNumber(),
            'unsignedDecimal' => fake()->randomNumber(),
            'unsignedInteger' => fake()->randomNumber(),
            'unsignedMediumInteger' => fake()->randomNumber(),
            'unsignedSmallInteger' => fake()->randomNumber(),
            'unsignedTinyInteger' => fake()->randomDigitNotNull(),
            'ulid' => (string) Str::ulid(),
            'uuid' => fake()->uuid(),
            'year' => fake()->year(),
        ];
    }
}
