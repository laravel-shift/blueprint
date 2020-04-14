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

    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    private $pivotTables = [];

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

            if (!empty($modelPivots = $model->pivotTables())) {
                foreach ($modelPivots as $pivotSegments) {
                    $pivotTable = $this->getPivotTableName($pivotSegments);
                    if (!isset($this->pivotTables[$pivotTable])) {
                        $this->pivotTables[$pivotTable] = [
                            'tableName' => $pivotTable,
                            'segments' => $pivotSegments
                        ];
                    }
                }
            }
        }

        if (!empty($this->pivotTables)) {
            foreach ($this->pivotTables as $pivotTable) {
                $path = $this->getPivotTablePath($pivotTable['tableName'], $sequential_timestamp->addSecond());
                $this->files->put($path, $this->populatePivotStub($stub, $pivotTable['segments']));
                $output['created'][] = $path;
            }
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

    protected function populatePivotStub(string $stub, array $segments)
    {
        $stub = str_replace('DummyClass', $this->getPivotClassName($segments), $stub);
        $stub = str_replace('DummyTable', $this->getPivotTableName($segments), $stub);
        $stub = str_replace('// definition...', $this->buildPivotTableDefinition($segments), $stub);

        return $stub;
    }

    protected function buildDefinition(Model $model)
    {
        $definition = '';

        /** @var \Blueprint\Models\Column $column */
        foreach ($model->columns() as $column) {
            $dataType = $column->dataType();
            if ($column->name() === 'id' && $dataType !== 'uuid') {
                $dataType = 'bigIncrements';
            } elseif ($column->dataType() === 'id') {
                $dataType = 'unsignedBigInteger';
            } elseif ($column->dataType() === 'uuid') {
                $dataType = 'uuid';
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

    protected function buildPivotTableDefinition(array $segments, $dataType = 'bigIncrements')
    {
        $definition = '';

        foreach ($segments as $segment) {
            $column = $segment . '_id';
            $definition .= self::INDENT . '$table->' . $dataType . "('{$column}');" . PHP_EOL;
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

    protected function getPivotTablePath($tableName, Carbon $timestamp)
    {
        return 'database/migrations/' . $timestamp->format('Y_m_d_His') . '_create_' . $tableName . '_table.php';
    }

    protected function isLaravel7orNewer()
    {
        return version_compare(App::version(), '7.0.0', '>=');
    }

    protected function getPivotClassName(array $segments)
    {
        return 'Create' . Str::studly($this->getPivotTableName($segments)) . 'PivotTable';
    }

    protected function getPivotTableName(array $segments)
    {
        $segments = array_map(function ($name) {
            return Str::snake($name);
        }, $segments);
        sort($segments);
        return strtolower(implode('_', $segments));
    }
}
