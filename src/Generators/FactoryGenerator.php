<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Shift\Faker\Registry as FakerRegistry;

class FactoryGenerator implements Generator
{
    const INDENT = '    ';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Tree
     */
    private $tree;

    private $imports = [];

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $output = [];
        $stub = $this->filesystem->stub('factory.stub');

        /**
         * @var \Blueprint\Models\Model $model
         */
        foreach ($tree->models() as $model) {
            $this->addImport($model, $model->fullyQualifiedClassName());

            $path = $this->getPath($model);

            if (!$this->filesystem->exists(dirname($path))) {
                $this->filesystem->makeDirectory(dirname($path), 0755, true);
            }

            $this->filesystem->put($path, $this->populateStub($stub, $model));

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
            $path = str_replace('\\', '/', $model->namespace()) . '/' . $path;
        }

        return 'database/factories/' . $path . 'Factory.php';
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('{{ model }}', $model->name(), $stub);
        $stub = str_replace('//', $this->buildDefinition($model), $stub);

        if (Blueprint::useReturnTypeHints()) {
            $stub = str_replace('definition()', 'definition(): array', $stub);
        }

        $stub = str_replace('{{ namespace }}', 'Database\Factories' . ($model->namespace() ? '\\' . $model->namespace() : ''), $stub);
        $stub = str_replace('use {{ namespacedModel }};', $this->buildImports($model), $stub);

        return $stub;
    }

    protected function buildDefinition(Model $model)
    {
        $definition = '';

        $fillable = $this->fillableColumns($model->columns());

        /**
         * @var \Blueprint\Models\Column $column
         */
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

                    if (Str::startsWith($column->name(), $foreign . '_')) {
                        $key = Str::after($column->name(), $foreign . '_');
                    } elseif (Str::startsWith($column->name(), Str::snake(Str::singular($foreign)) . '_')) {
                        $key = Str::after($column->name(), Str::snake(Str::singular($foreign)) . '_');
                    } elseif (!Str::endsWith($column->name(), '_id')) {
                        $key = $column->name();
                    }
                }

                $class = Str::studly(Str::singular($table));
                $reference = $this->fullyQualifyModelReference($class) ?? $model;

                $this->addImport($model, $reference->fullyQualifiedNamespace() . '\\' . $class);

                if ($key === 'id') {
                    $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                    $definition .= sprintf('%s::factory()', $class);
                    $definition .= ',' . PHP_EOL;
                } else {
                    $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                    $definition .= sprintf('%s::factory()->create()->%s', $class, $key);
                    $definition .= ',' . PHP_EOL;
                }
            } elseif ($column->dataType() === 'id' || ($column->dataType() === 'uuid' && Str::endsWith($column->name(), '_id'))) {
                $name = Str::beforeLast($column->name(), '_id');
                $class = Str::studly($column->attributes()[0] ?? $name);
                $reference = $this->fullyQualifyModelReference($class) ?? $model;

                $this->addImport($model, $reference->fullyQualifiedNamespace() . '\\' . $class);
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                $definition .= sprintf('%s::factory()', $class);
                $definition .= ',' . PHP_EOL;
            } elseif (in_array($column->dataType(), ['enum', 'set']) && !empty($column->attributes())) {
                $faker = FakerRegistry::fakerData($column->name()) ?? FakerRegistry::fakerDataType($column->dataType());
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                $definition .= '$this->faker->' . $faker;
                $definition .= ',' . PHP_EOL;
                $definition = str_replace(
                    "/** {$column->dataType()}_attributes **/",
                    json_encode($column->attributes()),
                    $definition
                );
            } elseif (in_array($column->dataType(), ['decimal', 'double', 'float'])) {
                $faker = FakerRegistry::fakerData($column->name()) ?? FakerRegistry::fakerDataType($column->dataType());
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                $definition .= '$this->faker->' . $faker;
                $definition .= ',' . PHP_EOL;

                $precision = min([65, intval($column->attributes()[0] ?? 10)]);
                $scale = min([30, max([0, intval($column->attributes()[1] ?? 0)])]);

                $definition = str_replace(
                    "/** {$column->dataType()}_attributes **/",
                    implode(', ', [$scale, 0, (str_repeat(9, $precision - $scale) . '.' . str_repeat(9, $scale))]),
                    $definition
                );
            } elseif (in_array($column->dataType(), ['json', 'jsonb'])) {
                $default = $column->defaultValue() ?? "{}";
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => '{$default}'," . PHP_EOL;
            } elseif ($column->dataType() === 'morphs') {
                if ($column->isNullable()) {
                    continue;
                }
                $definition .= sprintf('%s%s => $this->faker->%s,%s', str_repeat(self::INDENT, 3), "'{$column->name()}_id'", FakerRegistry::fakerDataType('id'), PHP_EOL);
                $definition .= sprintf('%s%s => $this->faker->%s,%s', str_repeat(self::INDENT, 3), "'{$column->name()}_type'", FakerRegistry::fakerDataType('string'), PHP_EOL);
            } elseif ($column->dataType() === 'rememberToken') {
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                $definition .= 'Str::random(10)';
                $definition .= ',' . PHP_EOL;
            } else {
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";

                $type = $column->dataType();
                if ($column->isUnsigned()) {
                    $type = 'unsigned' . $type;
                }

                $faker = FakerRegistry::fakerData($column->name()) ?? (FakerRegistry::fakerDataType($type) ?? FakerRegistry::fakerDataType($column->dataType()));

                if ($faker === null) {
                    $faker = 'word';
                }

                if (($faker === 'word') && (!empty($column->attributes()))) {
                    $faker = sprintf("regexify('[A-Za-z0-9]{%s}')", current($column->attributes()));
                }

                $definition .= '$this->faker->' . $faker;
                $definition .= ',' . PHP_EOL;
            }
        }

        return trim($definition);
    }

    protected function buildImports(Model $model)
    {
        $imports = array_unique($this->imports[$model->name()]);
        sort($imports);

        return implode(
            PHP_EOL,
            array_map(
                function ($class) {
                    return 'use ' . $class . ';';
                },
                $imports
            )
        );
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

        return array_filter(
            $columns,
            function (Column $column) {
                return !in_array('nullable', $column->modifiers());
            }
        );
    }

    private function fullyQualifyModelReference(string $model_name)
    {
        return $this->tree->modelForContext($model_name);
    }
}
