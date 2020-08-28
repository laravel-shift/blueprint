<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Model;
use Blueprint\Tree;
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

    const ON_DELETE_CLAUSES = [
        'cascade' => "->onDelete('cascade')",
        'restrict' => "->onDelete('restrict')",
        'null' => "->onDelete('set null')",
        'no_action' => "->onDelete('no action')",
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

    public function output(Tree $tree, $overwrite = false): array
    {
        $output = [];

        $created_pivot_tables = [];

        $stub = $this->files->stub('migration.stub');

        $sequential_timestamp = \Carbon\Carbon::now()->subSeconds(count($tree->models()));

        /** @var \Blueprint\Models\Model $model */
        foreach ($tree->models() as $model) {
            $path = $this->getPath($model, $sequential_timestamp->addSecond(), $overwrite);
            $action = $this->files->exists($path) ? 'updated' : 'created';
            $this->files->put($path, $this->populateStub($stub, $model));

            $output[$action][] = $path;

            if (! empty($model->pivotTables())) {
                foreach ($model->pivotTables() as $pivotSegments) {
                    $pivotTable = $this->getPivotTableName($pivotSegments);
                    $created_pivot_tables[$pivotTable] = $pivotSegments;
                }
            }
        }

        foreach ($created_pivot_tables as $pivotTable => $pivotSegments) {
            $path = $this->getPivotTablePath($pivotTable, $sequential_timestamp, $overwrite);
            $action = $this->files->exists($path) ? 'updated' : 'created';
            $this->files->put($path, $this->populatePivotStub($stub, $pivotSegments));
            $created_pivot_tables[] = $pivotTable;
            $output[$action][] = $path;
        }

        return $output;
    }

    public function types(): array
    {
        return ['migrations'];
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('{{ class }}', $this->getClassName($model), $stub);
        $stub = str_replace('{{ table }}', $model->tableName(), $stub);
        $stub = str_replace('{{ definition }}', $this->buildDefinition($model), $stub);

        return $stub;
    }

    protected function populatePivotStub(string $stub, array $segments)
    {
        $stub = str_replace('{{ class }}', $this->getPivotClassName($segments), $stub);
        $stub = str_replace('{{ table }}', $this->getPivotTableName($segments), $stub);
        $stub = str_replace('{{ definition }}', $this->buildPivotTableDefinition($segments), $stub);

        return $stub;
    }

    protected function buildDefinition(Model $model)
    {
        $definition = '';

        /** @var \Blueprint\Models\Column $column */
        foreach ($model->columns() as $column) {
            $dataType = $column->dataType();

            if ($column->name() === 'id' && $dataType === 'id') {
                $dataType = 'bigIncrements';
            } elseif ($dataType === 'id') {
                $dataType = 'unsignedBigInteger';
            }

            if (in_array($dataType, self::UNSIGNABLE_TYPES) && in_array('unsigned', $column->modifiers())) {
                $dataType = 'unsigned'.ucfirst($dataType);
            }

            if (in_array($dataType, self::NULLABLE_TYPES) && $column->isNullable()) {
                $dataType = 'nullable'.ucfirst($dataType);
            }

            $column_definition = self::INDENT;
            if ($dataType === 'bigIncrements' && $this->isLaravel7orNewer()) {
                $column_definition .= '$table->id(';
            } elseif ($dataType === 'rememberToken') {
                $column_definition .= '$table->rememberToken(';
            } else {
                $column_definition .= '$table->'.$dataType."('{$column->name()}'";
            }

            if (! empty($column->attributes()) && ! in_array($column->dataType(), ['id', 'uuid'])) {
                $column_definition .= ', ';
                if (in_array($column->dataType(), ['set', 'enum'])) {
                    $column_definition .= json_encode($column->attributes());
                } else {
                    $column_definition .= implode(', ', $column->attributes());
                }
            }
            $column_definition .= ')';

            $modifiers = $column->modifiers();

            $foreign = '';
            $foreign_modifier = $column->isForeignKey();

            if ($this->shouldAddForeignKeyConstraint($column)) {
                $foreign = $this->buildForeignKey(
                    $column->name(),
                    $foreign_modifier === 'foreign' ? null : $foreign_modifier,
                    $column->dataType(),
                    $column->attributes(),
                    $column->modifiers()
                );

                if ($column->dataType() === 'id' && $this->isLaravel7orNewer()) {
                    $column_definition = $foreign;
                    $foreign = '';
                }

                // TODO: unset the proper modifier
                $modifiers = collect($modifiers)->reject(function ($modifier) {
                    return (is_array($modifier) && key($modifier) === 'foreign')
                        || (is_array($modifier) && key($modifier) === 'onDelete')
                        || $modifier === 'foreign'
                        || ($modifier === 'nullable' && $this->isLaravel7orNewer());
                });
            }

            foreach ($modifiers as $modifier) {
                if (is_array($modifier)) {
                    $column_definition .= '->'.key($modifier).'('.current($modifier).')';
                } elseif ($modifier === 'unsigned' && Str::startsWith($dataType, 'unsigned')) {
                    continue;
                } elseif ($modifier === 'nullable' && Str::startsWith($dataType, 'nullable')) {
                    continue;
                } else {
                    $column_definition .= '->'.$modifier.'()';
                }
            }

            $column_definition .= ';'.PHP_EOL;
            if (! empty($foreign)) {
                $column_definition .= $foreign.';'.PHP_EOL;
            }

            $definition .= $column_definition;
        }

        if ($model->usesSoftDeletes()) {
            $definition .= self::INDENT.'$table->'.$model->softDeletesDataType().'();'.PHP_EOL;
        }

        if ($model->morphTo()) {
            $definition .= self::INDENT.sprintf('$table->unsignedBigInteger(\'%s\');', Str::lower($model->morphTo().'_id')).PHP_EOL;
            $definition .= self::INDENT.sprintf('$table->string(\'%s\');', Str::lower($model->morphTo().'_type')).PHP_EOL;
        }

        foreach ($model->indexes() as $index) {
            $index_definition = self::INDENT;
            $index_definition .= '$table->'.$index->type();
            if (count($index->columns()) > 1) {
                $index_definition .= "(['".implode("', '", $index->columns())."']);".PHP_EOL;
            } else {
                $index_definition .= "('{$index->columns()[0]}');".PHP_EOL;
            }
            $definition .= $index_definition;
        }
        if ($model->usesTimestamps()) {
            $definition .= self::INDENT.'$table->'.$model->timestampsDataType().'();'.PHP_EOL;
        }

        return trim($definition);
    }

    protected function buildPivotTableDefinition(array $segments)
    {
        $definition = '';

        foreach ($segments as $segment) {
            $column = Str::before(Str::lower($segment), ':');
            $references = 'id';
            $on = Str::plural($column);
            $foreign = Str::singular($column).'_'.$references;

            if (! $this->isLaravel7orNewer()) {
                $definition .= self::INDENT.'$table->unsignedBigInteger(\''.$foreign.'\');'.PHP_EOL;
            }

            if (config('blueprint.use_constraints')) {
                $definition .= $this->buildForeignKey($foreign, $on, 'id').';'.PHP_EOL;
            } elseif ($this->isLaravel7orNewer()) {
                $definition .= self::INDENT.'$table->foreignId(\''.$foreign.'\');'.PHP_EOL;
            }
        }

        return trim($definition);
    }

    protected function buildForeignKey(string $column_name, ?string $on, string $type, array $attributes = [], array $modifiers = [])
    {
        if (is_null($on)) {
            $table = Str::plural(Str::beforeLast($column_name, '_'));
            $column = Str::afterLast($column_name, '_');
        } elseif (Str::contains($on, '.')) {
            [$table, $column] = explode('.', $on);
            $table = Str::snake($table);
        } else {
            $table = Str::plural($on);
            $column = Str::afterLast($column_name, '_');
        }

        if ($type === 'id' && ! empty($attributes)) {
            $table = Str::lower(Str::plural($attributes[0]));
        }

        $on_delete_clause = collect($modifiers)->firstWhere('onDelete');
        $on_delete_clause = $on_delete_clause ? $on_delete_clause['onDelete'] : config('blueprint.on_delete', 'cascade');
        $on_delete_suffix = self::ON_DELETE_CLAUSES[$on_delete_clause];

        if ($this->isLaravel7orNewer() && $type === 'id') {
            $prefix = in_array('nullable', $modifiers)
                ? '$table->foreignId'."('{$column_name}')->nullable()"
                : '$table->foreignId'."('{$column_name}')";

            if ($on_delete_clause === 'cascade') {
                $on_delete_suffix = '->cascadeOnDelete()';
            }
            if ($column_name === Str::singular($table).'_'.$column) {
                return self::INDENT."{$prefix}->constrained(){$on_delete_suffix}";
            }
            if ($column === 'id') {
                return self::INDENT."{$prefix}->constrained('{$table}'){$on_delete_suffix}";
            }

            return self::INDENT."{$prefix}->constrained('{$table}', '{$column}'){$on_delete_suffix}";
        }

        return self::INDENT.'$table->foreign'."('{$column_name}')->references('{$column}')->on('{$table}'){$on_delete_suffix}";
    }

    protected function getClassName(Model $model)
    {
        return 'Create'.Str::studly($model->tableName()).'Table';
    }

    protected function getPath(Model $model, Carbon $timestamp, $overwrite = false)
    {
        return $this->getTablePath($model->tableName(), $timestamp, $overwrite);
    }

    protected function getPivotTablePath($tableName, Carbon $timestamp, $overwrite = false)
    {
        return $this->getTablePath($tableName, $timestamp, $overwrite);
    }

    protected function getTablePath($tableName, Carbon $timestamp, $overwrite = false)
    {
        $dir = 'database/migrations/';
        $name = '_create_'.$tableName.'_table.php';

        $file = $overwrite ? collect($this->files->files($dir))->first(function ($file) use ($tableName) {
            return str_contains($file, $tableName);
        }) : false;

        return $file ? (string) $file : $dir.$timestamp->format('Y_m_d_His').$name;
    }

    protected function isLaravel7orNewer()
    {
        return version_compare(App::version(), '7.0.0', '>=');
    }

    protected function getPivotClassName(array $segments)
    {
        return 'Create'.Str::studly($this->getPivotTableName($segments)).'Table';
    }

    protected function getPivotTableName(array $segments)
    {
        $isCustom = collect($segments)
            ->filter(function ($segment) {
                return Str::contains($segment, ':');
            })->first();

        if ($isCustom) {
            $table = Str::after($isCustom, ':');

            return $table;
        }

        $segments = array_map(function ($name) {
            return Str::snake($name);
        }, $segments);
        sort($segments);

        return strtolower(implode('_', $segments));
    }

    private function shouldAddForeignKeyConstraint(\Blueprint\Models\Column $column)
    {
        if ($column->name() === 'id') {
            return false;
        }

        if ($column->isForeignKey()) {
            return true;
        }

        return in_array($column->dataType(), ['id', 'uuid']) && config('blueprint.use_constraints');
    }
}
