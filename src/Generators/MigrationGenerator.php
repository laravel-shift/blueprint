<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class MigrationGenerator implements Generator
{
    const INDENT = '            ';

    const NULLABLE_TYPES = [
        'morphs',
        'uuidMorphs',
    ];

    const UNSIGNABLE_TYPES = [
      'bigInteger',
      'decimal',
      'integer',
      'mediumInteger',
      'smallInteger',
      'tinyInteger',
    ];

    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->stub('migration.stub');

        $sequential_timestamp = \Carbon\Carbon::now()->subSeconds(count($tree['models']));

        /** @var \Blueprint\Models\Model $model */
        foreach ($tree['models'] as $model) {
            $path = $this->getPath($model, $sequential_timestamp->addSecond());
            $this->files->put($path, $this->populateStub($stub, $model));

            $output['created'][] = $path;
        }

        return $output;
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('DummyClass', $this->getClassName($model), $stub);
        $stub = str_replace('DummyTable', $model->tableName(), $stub);
        $stub = str_replace('// definition...', $this->buildDefinition($model), $stub);

        return $stub;
    }

    protected function buildDefinition(Model $model)
    {
        $definition = '';

        /** @var \Blueprint\Models\Column $column */
        foreach ($model->columns() as $column) {
            $dataType = $column->dataType();
            if ($column->name() === 'id' && $dataType === 'id' && $model->usesPrimaryKey()) {
                $dataType = 'bigIncrements';
            } elseif ($dataType === 'id') {
                $dataType = 'unsignedBigInteger';
            }

            if (in_array($dataType, self::UNSIGNABLE_TYPES) && in_array('unsigned', $column->modifiers())) {
                $dataType = 'unsigned' . ucfirst($dataType);
            }

            if (in_array($dataType, self::NULLABLE_TYPES) && in_array('nullable', $column->modifiers())) {
                $dataType = 'nullable' . ucfirst($dataType);
            }

            if ($dataType === 'bigIncrements' && $this->isLaravel7orNewer()) {
                $definition .= self::INDENT . '$table->id(';
            } else {
                $definition .= self::INDENT . '$table->' . $dataType . "('{$column->name()}'";
            }

            if (!empty($column->attributes()) && !in_array($column->dataType(), ['id', 'uuid'])) {
                $definition .= ', ';
                if (in_array($column->dataType(), ['set', 'enum'])) {
                    $definition .= json_encode($column->attributes());
                } else {
                    $definition .= implode(', ', $column->attributes());
                }
            }
            $definition .= ')';

            $foreign = '';

            foreach ($column->modifiers() as $modifier) {
                if (is_array($modifier)) {
                    if (key($modifier) === 'foreign') {
                        $foreign = self::INDENT . '$table->foreign(' . "'{$column->name()}')->references('id')->on('" . Str::lower(Str::plural(current($modifier))) . "')->onDelete('cascade');" . PHP_EOL;
                    } else {
                        $definition .= '->' . key($modifier) . '(' . current($modifier) . ')';
                    }
                } elseif ($modifier === 'unsigned' && Str::startsWith($dataType, 'unsigned')) {
                    continue;
                } elseif ($modifier === 'nullable' && Str::startsWith($dataType, 'nullable')) {
                    continue;
                } else {
                    $definition .= '->' . $modifier . '()';
                }
            }

            $definition .= ';' . PHP_EOL . $foreign;
        }

        if ($model->usesSoftDeletes()) {
            $definition .= self::INDENT . '$table->' . $model->softDeletesDataType() . '();' . PHP_EOL;
        }

        if ($model->usesTimestamps()) {
            $definition .= self::INDENT . '$table->' . $model->timestampsDataType() . '();' . PHP_EOL;
        }

        return trim($definition);
    }

    protected function getClassName(Model $model)
    {
        return 'Create' . Str::studly($model->tableName()) . 'Table';
    }

    protected function getPath(Model $model, Carbon $timestamp)
    {
        return 'database/migrations/' . $timestamp->format('Y_m_d_His') . '_create_' . $model->tableName() . '_table.php';
    }

    protected function isLaravel7orNewer()
    {
        return version_compare(App::version(), '7.0.0', '>=');
    }
}
