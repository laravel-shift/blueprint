<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Illuminate\Support\Str;

class FactoryGenerator implements Generator
{
    const INDENT = '        ';

    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    private $imports = [];

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->stub('factory.stub');

        /** @var \Blueprint\Models\Model $model */
        foreach ($tree['models'] as $model) {
            $this->addImport($model, 'Faker\Generator as Faker');
            $this->addImport($model, $model->fullyQualifiedClassName());

            $path = $this->getPath($model);

            if (! $this->files->exists(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0755, true);
            }

            $this->files->put($path, $this->populateStub($stub, $model));

            $output['created'][] = $path;
        }

        return $output;
    }

    public function types(): array
    {
        return ['factories'];
    }

    protected function getPath(Model $model)
    {
        $path = $model->name();
        if ($model->namespace()) {
            $path = str_replace('\\', '/', $model->namespace()).'/'.$path;
        }

        return 'database/factories/'.$path.'Factory.php';
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('DummyClass', $model->name(), $stub);
        $stub = str_replace('// definition...', $this->buildDefinition($model), $stub);
        $stub = str_replace('// imports...', $this->buildImports($model), $stub);

        return $stub;
    }

    protected function buildDefinition(Model $model)
    {
        $definition = '';

        $fillable = $this->fillableColumns($model->columns());

        /** @var \Blueprint\Models\Column $column */
        foreach ($fillable as $column) {
            if ($column->name() === 'id') {
                continue;
            }

            if (Str::startsWith($column->dataType(), 'nullable')) {
                continue;
            }

            $foreign = $column->isForeignKey();
            if ($foreign) {
                $table = Str::beforeLast($column->name(), '_id');
                $key = 'id';

                if (Str::contains($foreign, '.')) {
                    [$table, $key] = explode('.', $foreign);
                } elseif ($foreign !== 'foreign') {
                    $table = $foreign;

                    if (Str::startsWith($column->name(), $foreign.'_')) {
                        $key = Str::after($column->name(), $foreign.'_');
                    } elseif (Str::startsWith($column->name(), Str::snake(Str::singular($foreign)).'_')) {
                        $key = Str::after($column->name(), Str::snake(Str::singular($foreign)).'_');
                    } elseif (! Str::endsWith($column->name(), '_id')) {
                        $key = $column->name();
                    }
                }

                $class = Str::studly(Str::singular($table));

                if ($key === 'id') {
                    $definition .= self::INDENT."'{$column->name()}' => ";
                    $definition .= sprintf('factory(%s::class)', '\\'.$model->fullyQualifiedNamespace().'\\'.$class);
                    $definition .= ','.PHP_EOL;
                } else {
                    $definition .= self::INDENT."'{$column->name()}' => function () {";
                    $definition .= PHP_EOL;
                    $definition .= self::INDENT.'    '.sprintf('return factory(%s::class)->create()->%s;', '\\'.$model->fullyQualifiedNamespace().'\\'.$class, $key);
                    $definition .= PHP_EOL;
                    $definition .= self::INDENT.'},'.PHP_EOL;
                }
            } elseif ($column->dataType() === 'id' || ($column->dataType() === 'uuid' && Str::endsWith($column->name(), '_id'))) {
                $name = Str::beforeLast($column->name(), '_id');
                $class = Str::studly($column->attributes()[0] ?? $name);

                $definition .= self::INDENT."'{$column->name()}' => ";
                $definition .= sprintf('factory(%s::class)', '\\'.$model->fullyQualifiedNamespace().'\\'.$class);
                $definition .= ','.PHP_EOL;
            } elseif (in_array($column->dataType(), ['enum', 'set']) && ! empty($column->attributes())) {
                $definition .= self::INDENT."'{$column->name()}' => ";
                $faker = $this->fakerData($column->name()) ?? $this->fakerDataType($column->dataType());
                $definition .= '$faker->'.$faker;
                $definition .= ','.PHP_EOL;
                $definition = str_replace(
                    "/** {$column->dataType()}_attributes **/",
                    json_encode($column->attributes()),
                    $definition
                );
            } elseif (in_array($column->dataType(), ['decimal', 'double', 'float'])) {
                $definition .= self::INDENT."'{$column->name()}' => ";
                $faker = $this->fakerData($column->name()) ?? $this->fakerDataType($column->dataType());
                $definition .= '$faker->'.$faker;
                $definition .= ','.PHP_EOL;

                $precision = min([65, intval($column->attributes()[0] ?? 10)]);
                $scale = min([30, max([0, intval($column->attributes()[1] ?? 0)])]);

                $definition = str_replace(
                    "/** {$column->dataType()}_attributes **/",
                    implode(', ', [$scale, 0, (str_repeat(9, $precision - $scale).'.'.str_repeat(9, $scale))]),
                    $definition
                );
            } elseif (in_array($column->dataType(), ['json', 'jsonb'])) {
                $default = $column->defaultValue() ?? "'{}'";
                $definition .= self::INDENT."'{$column->name()}' => {$default},".PHP_EOL;
            } elseif ($column->dataType() === 'morphs') {
                if ($column->isNullable()) {
                    continue;
                }
                $definition .= sprintf('%s%s => $faker->%s,%s', self::INDENT, "'{$column->name()}_id'", self::fakerDataType('id'), PHP_EOL);
                $definition .= sprintf('%s%s => $faker->%s,%s', self::INDENT, "'{$column->name()}_type'", self::fakerDataType('string'), PHP_EOL);
            } elseif ($column->dataType() === 'rememberToken') {
                $this->addImport($model, 'Illuminate\Support\Str');
                $definition .= self::INDENT."'{$column->name()}' => ";
                $definition .= 'Str::random(10)';
                $definition .= ','.PHP_EOL;
            } else {
                $definition .= self::INDENT."'{$column->name()}' => ";

                $type = $column->dataType();
                if ($column->isUnsigned()) {
                    $type = 'unsigned'.$type;
                }

                $faker = self::fakerData($column->name()) ?? (self::fakerDataType($type) ?? self::fakerDataType($column->dataType()));

                if ($faker === null) {
                    $faker = 'word';
                }

                $definition .= '$faker->'.$faker;
                $definition .= ','.PHP_EOL;
            }
        }

        return trim($definition);
    }

    protected function buildImports(Model $model)
    {
        $imports = array_unique($this->imports[$model->name()]);
        sort($imports);

        return implode(PHP_EOL, array_map(function ($class) {
            return 'use '.$class.';';
        }, $imports));
    }

    private function addImport(Model $model, $class)
    {
        $this->imports[$model->name()][] = $class;
    }

    private function fillableColumns(array $columns): array
    {
        if (config('blueprint.fake_nullables')) {
            return $columns;
        }

        return array_filter($columns, function (Column $column) {
            return ! in_array('nullable', $column->modifiers());
        });
    }

    public static function fakerData(string $name)
    {
        static $fakeableNames = [
            'address1' => 'streetAddress',
            'address2' => 'secondaryAddress',
            'city' => 'city',
            'company' => 'company',
            'content' => 'paragraphs(3, true)',
            'country' => 'country',
            'description' => 'text',
            'email' => 'safeEmail',
            'first_name' => 'firstName',
            'firstname' => 'firstName',
            'guid' => 'uuid',
            'last_name' => 'lastName',
            'lastname' => 'lastName',
            'lat' => 'latitude',
            'latitude' => 'latitude',
            'lng' => 'longitude',
            'longitude' => 'longitude',
            'name' => 'name',
            'password' => 'password',
            'phone' => 'phoneNumber',
            'phone_number' => 'phoneNumber',
            'postal_code' => 'postcode',
            'postcode' => 'postcode',
            'slug' => 'slug',
            'ssn' => 'ssn',
            'street' => 'streetName',
            'summary' => 'text',
            'title' => 'sentence(4)',
            'url' => 'url',
            'user_name' => 'userName',
            'username' => 'userName',
            'uuid' => 'uuid',
            'zip' => 'postcode',
        ];

        return $fakeableNames[$name] ?? null;
    }

    public static function fakerDataType(string $type)
    {
        $fakeableTypes = [
            'biginteger' => 'numberBetween(-100000, 100000)',
            'binary' => 'sha256',
            'boolean' => 'boolean',
            'char' => 'randomLetter',
            'date' => 'date()',
            'datetime' => 'dateTime()',
            'datetimetz' => 'dateTime()',
            'decimal' => 'randomFloat(/** decimal_attributes **/)',
            'double' => 'randomFloat(/** double_attributes **/)',
            'enum' => 'randomElement(/** enum_attributes **/)',
            'float' => 'randomFloat(/** float_attributes **/)',
            'geometry' => 'word',
            'geometrycollection' => 'word',
            'guid' => 'uuid',
            'id' => 'randomDigitNotNull',
            'integer' => 'numberBetween(-10000, 10000)',
            'ipaddress' => 'ipv4',
            'linestring' => 'word',
            'longtext' => 'text',
            'macaddress' => 'macAddress',
            'mediuminteger' => 'numberBetween(-10000, 10000)',
            'mediumtext' => 'text',
            'morphs_id' => 'randomDigitNotNull',
            'morphs_type' => 'word',
            'multilinestring' => 'word',
            'multipoint' => 'word',
            'multipolygon' => 'word',
            'nullablemorphs' => null,
            'nullabletimestamps' => null,
            'nullableuuidmorphs' => null,
            'point' => 'word',
            'polygon' => 'word',
            'set' => 'randomElement(/** set_attributes **/)',
            'smallint' => 'numberBetween(-1000, 1000)',
            'smallinteger' => 'numberBetween(-1000, 1000)',
            'string' => 'word',
            'text' => 'text',
            'time' => 'time()',
            'timestamp' => 'dateTime()',
            'timestamptz' => 'dateTime()',
            'timetz' => 'time()',
            'tinyinteger' => 'numberBetween(-8, 8)',
            'unsignedbiginteger' => 'randomNumber()',
            'unsigneddecimal' => 'randomNumber()',
            'unsignedinteger' => 'randomNumber()',
            'unsignedmediuminteger' => 'randomNumber()',
            'unsignedsmallinteger' => 'randomNumber()',
            'unsignedtinyinteger' => 'randomDigitNotNull',
            'uuid' => 'uuid',
            'uuidmorphs' => 'word',
            'year' => 'year()',
        ];

        return $fakeableTypes[strtolower($type)] ?? null;
    }
}
