<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Shift\Faker\Registry as FakerRegistry;
use Blueprint\Tree;
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

    public function output(Tree $tree): array
    {
        $output = [];

        $stub = $this->files->stub('factory.stub');

        /** @var \Blueprint\Models\Model $model */
        foreach ($tree->models() as $model) {
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
        $stub = str_replace('{{ class }}', $model->name(), $stub);
        $stub = str_replace('{{ definition }}', $this->buildDefinition($model), $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($model), $stub);

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
                $faker = FakerRegistry::fakerData($column->name()) ?? FakerRegistry::fakerDataType($column->dataType());
                $definition .= '$faker->'.$faker;
                $definition .= ','.PHP_EOL;
                $definition = str_replace(
                    "/** {$column->dataType()}_attributes **/",
                    json_encode($column->attributes()),
                    $definition
                );
            } elseif (in_array($column->dataType(), ['decimal', 'double', 'float'])) {
                $definition .= self::INDENT."'{$column->name()}' => ";
                $faker = FakerRegistry::fakerData($column->name()) ?? FakerRegistry::fakerDataType($column->dataType());
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
                $definition .= sprintf('%s%s => $faker->%s,%s', self::INDENT, "'{$column->name()}_id'", FakerRegistry::fakerDataType('id'), PHP_EOL);
                $definition .= sprintf('%s%s => $faker->%s,%s', self::INDENT, "'{$column->name()}_type'", FakerRegistry::fakerDataType('string'), PHP_EOL);
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

                $faker = FakerRegistry::fakerData($column->name()) ?? (FakerRegistry::fakerDataType($type) ?? FakerRegistry::fakerDataType($column->dataType()));

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
}
