<?php

namespace Blueprint\Generators;

use Blueprint\Concerns\HandlesImports;
use Blueprint\Concerns\HandlesTraits;
use Blueprint\Contracts\Generator;
use Blueprint\Contracts\Model as BlueprintModel;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Blueprint\Tree;
use Illuminate\Support\Str;
use Shift\Faker\Registry as FakerRegistry;

class FactoryGenerator extends AbstractClassGenerator implements Generator
{
    use HandlesImports, HandlesTraits;

    const INDENT = '    ';

    protected array $types = ['factories'];

    public function output(Tree $tree): array
    {
        $this->tree = $tree;
        $stub = $this->filesystem->stub('factory.stub');

        /**
         * @var \Blueprint\Models\Model $model
         */
        foreach ($tree->models() as $model) {
            $this->addImport($model, $model->fullyQualifiedClassName());

            $path = $this->getPath($model);

            $this->create($path, $this->populateStub($stub, $model));
        }

        return $this->output;
    }

    protected function getPath(BlueprintModel $blueprintModel): string
    {
        $path = $blueprintModel->name();
        if ($blueprintModel->namespace()) {
            $path = str_replace('\\', '/', $blueprintModel->namespace()) . '/' . $path;
        }

        return 'database/factories/' . $path . 'Factory.php';
    }

    protected function populateStub(string $stub, Model $model): string
    {
        $stub = str_replace('{{ model }}', $model->name(), $stub);
        $stub = str_replace('//', $this->buildDefinition($model), $stub);
        $stub = str_replace('{{ namespace }}', 'Database\Factories' . ($model->namespace() ? '\\' . $model->namespace() : ''), $stub);
        $stub = str_replace('use {{ namespacedModel }};', $this->buildImports($model), $stub);

        return $stub;
    }

    protected function buildDefinition(Model $model): string
    {
        $definition = '';

        $fillable = $this->fillableColumns($model->columns());

        /**
         * @var \Blueprint\Models\Column $column
         */
        foreach ($fillable as $column) {
            if (in_array($column->name(), ['id', 'softdeletes', 'softdeletestz'])) {
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
            } elseif ($column->dataType() === 'id' || (in_array($column->dataType(), ['uuid', 'ulid']) && Str::endsWith($column->name(), '_id'))) {
                $name = Str::beforeLast($column->name(), '_id');
                $class = Str::studly($column->attributes()[0] ?? $name);
                $reference = $this->fullyQualifyModelReference($class) ?? $model;

                $this->addImport($model, $reference->fullyQualifiedNamespace() . '\\' . $class);
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                $definition .= sprintf('%s::factory()', $class);
                $definition .= ',' . PHP_EOL;
            } elseif (in_array($column->dataType(), ['enum', 'set']) && !empty($column->attributes())) {
                $faker = FakerRegistry::fakerDataType($column->dataType()) ?? FakerRegistry::fakerData($column->name());
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                $definition .= 'fake()->' . $faker;
                $definition .= ',' . PHP_EOL;
                $definition = str_replace(
                    "/** {$column->dataType()}_attributes **/",
                    json_encode($column->attributes()),
                    $definition
                );
            } elseif (in_array($column->dataType(), ['decimal', 'double', 'float'])) {
                $faker = FakerRegistry::fakerData($column->name()) ?? FakerRegistry::fakerDataType($column->dataType());
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                $definition .= 'fake()->' . $faker;
                $definition .= ',' . PHP_EOL;

                $precision = min([65, intval($column->attributes()[0] ?? 10)]);
                $scale = min([30, max([0, intval($column->attributes()[1] ?? 0)])]);

                $definition = str_replace(
                    "/** {$column->dataType()}_attributes **/",
                    implode(', ', [$scale, 0, (str_repeat(9, $precision - $scale) . '.' . str_repeat(9, $scale))]),
                    $definition
                );
            } elseif (in_array($column->dataType(), ['json', 'jsonb'])) {
                $default = $column->defaultValue() ?? '{}';
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => '{$default}'," . PHP_EOL;
            } elseif ($column->dataType() === 'morphs') {
                if ($column->isNullable()) {
                    continue;
                }
                $definition .= sprintf('%s%s => fake()->%s,%s', str_repeat(self::INDENT, 3), "'{$column->name()}_id'", FakerRegistry::fakerDataType('id'), PHP_EOL);
                $definition .= sprintf('%s%s => fake()->%s,%s', str_repeat(self::INDENT, 3), "'{$column->name()}_type'", FakerRegistry::fakerDataType('string'), PHP_EOL);
            } elseif ($column->dataType() === 'rememberToken') {
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                $definition .= 'Str::random(10)';
                $definition .= ',' . PHP_EOL;
            } elseif ($column->dataType() === 'ulid') {
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";
                $definition .= '(string) Str::ulid()';
                $definition .= ',' . PHP_EOL;
            } else {
                $definition .= str_repeat(self::INDENT, 3) . "'{$column->name()}' => ";

                $type = $column->dataType();
                if ($column->isUnsigned()) {
                    $type = 'unsigned' . $type;
                }

                $faker = FakerRegistry::fakerData($column->name()) ?? (FakerRegistry::fakerDataType($type) ?? FakerRegistry::fakerDataType($column->dataType()));

                if ($faker === null) {
                    $faker = 'word()';
                }

                if ($faker === 'word()' && !empty($column->attributes())) {
                    $faker = sprintf("regexify('[A-Za-z0-9]{%s}')", current($column->attributes()));
                }

                $definition .= 'fake()->' . $faker;
                $definition .= ',' . PHP_EOL;
            }
        }

        if (empty($definition)) {
            return '//';
        }

        return trim($definition);
    }

    protected function fillableColumns(array $columns): array
    {
        if (config('blueprint.fake_nullables')) {
            return $columns;
        }

        $nonNullableColumns = array_filter(
            $columns,
            fn (Column $column) => !in_array('nullable', $column->modifiers())
        );

        return array_filter(
            $nonNullableColumns,
            fn (Column $column) => $column->dataType() !== 'softDeletes'
        );
    }

    private function fullyQualifyModelReference(string $model_name)
    {
        return $this->tree->modelForContext($model_name);
    }
}
