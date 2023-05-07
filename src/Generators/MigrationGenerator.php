<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Model;
use Blueprint\Tree;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class MigrationGenerator extends AbstractClassGenerator implements Generator
{
    protected $types = ['migrations'];

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

    const ON_UPDATE_CLAUSES = [
        'cascade' => "->onUpdate('cascade')",
        'restrict' => "->onUpdate('restrict')",
        'null' => "->onUpdate('set null')",
        'no_action' => "->onUpdate('no action')",
    ];

    const UNSIGNABLE_TYPES = [
        'bigInteger',
        'decimal',
        'integer',
        'mediumInteger',
        'smallInteger',
        'tinyInteger',
    ];

    const INTEGER_TYPES = [
        'integer',
        'tinyInteger',
        'smallInteger',
        'mediumInteger',
        'bigInteger',
        'unsignedInteger',
        'unsignedTinyInteger',
        'unsignedSmallInteger',
        'unsignedMediumInteger',
        'unsignedBigInteger',
    ];

    private $hasForeignKeyConstraints = false;

    public function output(Tree $tree, $overwrite = false): array
    {
        $tables = ['tableNames' => [], 'pivotTableNames' => [], 'polymorphicManyToManyTables' => []];

        $stub = $this->filesystem->stub('migration.stub');
        /**
         * @var \Blueprint\Models\Model $model
         */
        foreach ($tree->models() as $model) {
            $tables['tableNames'][$model->tableName()] = $this->populateStub($stub, $model);
            if (!empty($model->pivotTables())) {
                foreach ($model->pivotTables() as $pivotSegments) {
                    $pivotTableName = $this->getPivotTableName($pivotSegments);
                    $tables['pivotTableNames'][$pivotTableName] = $this->populatePivotStub($stub, $pivotSegments);
                }
            }

            if (!empty($model->polymorphicManyToManyTables())) {
                foreach ($model->polymorphicManyToManyTables() as $tableName) {
                    $tables['polymorphicManyToManyTables'][Str::lower(Str::plural(Str::singular($tableName) . 'able'))] = $this->populatePolyStub($stub, $tableName);
                }
            }
        }

        return $this->createMigrations($tables, $overwrite);
    }

    protected function createMigrations(array $tables, $overwrite = false): array
    {
        $sequential_timestamp = \Carbon\Carbon::now()->copy()->subSeconds(
            collect($tables['tableNames'])->merge($tables['pivotTableNames'])->merge($tables['polymorphicManyToManyTables'])->count()
        );

        foreach ($tables['tableNames'] as $tableName => $data) {
            $path = $this->getTablePath($tableName, $sequential_timestamp->addSecond(), $overwrite);
            $action = $this->filesystem->exists($path) ? 'updated' : 'created';
            $this->filesystem->put($path, $data);
            $this->output[$action][] = $path;
        }

        foreach ($tables['pivotTableNames'] as $tableName => $data) {
            $path = $this->getTablePath($tableName, $sequential_timestamp->addSecond(), $overwrite);
            $action = $this->filesystem->exists($path) ? 'updated' : 'created';
            $this->filesystem->put($path, $data);

            $this->output[$action][] = $path;
        }

        foreach ($tables['polymorphicManyToManyTables'] as $tableName => $data) {
            $path = $this->getTablePath($tableName, $sequential_timestamp->addSecond(), $overwrite);
            $action = $this->filesystem->exists($path) ? 'updated' : 'created';
            $this->filesystem->put($path, $data);
            $this->output[$action][] = $path;
        }

        return $this->output;
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('{{ table }}', $model->tableName(), $stub);
        $stub = str_replace('{{ definition }}', $this->buildDefinition($model), $stub);

        if ($this->hasForeignKeyConstraints) {
            $stub = $this->disableForeignKeyConstraints($stub);
        }

        return $stub;
    }

    protected function populatePivotStub(string $stub, array $segments)
    {
        $stub = str_replace('{{ table }}', $this->getPivotTableName($segments), $stub);
        $stub = str_replace('{{ definition }}', $this->buildPivotTableDefinition($segments), $stub);

        if ($this->hasForeignKeyConstraints) {
            $stub = $this->disableForeignKeyConstraints($stub);
        }

        return $stub;
    }

    protected function populatePolyStub(string $stub, string $parentTable)
    {
        $stub = str_replace('{{ table }}', $this->getPolyTableName($parentTable), $stub);
        $stub = str_replace('{{ definition }}', $this->buildPolyTableDefinition($parentTable), $stub);

        if ($this->hasForeignKeyConstraints) {
            $stub = $this->disableForeignKeyConstraints($stub);
        }

        return $stub;
    }

    protected function buildDefinition(Model $model)
    {
        $definition = '';

        /**
         * @var \Blueprint\Models\Column $column
         */
        foreach ($model->columns() as $column) {
            $dataType = $column->dataType();

            if ($column->name() === 'id' && $dataType === 'id') {
                $dataType = 'bigIncrements';
            } elseif ($dataType === 'id') {
                if ($model->isPivot()) {
                    // TODO: what if constraints are enabled?
                    $dataType = 'foreignId';
                } else {
                    $dataType = 'unsignedBigInteger';
                }
            }

            if (in_array($dataType, self::UNSIGNABLE_TYPES) && in_array('unsigned', $column->modifiers())) {
                $dataType = 'unsigned' . ucfirst($dataType);
            }

            if (in_array($dataType, self::NULLABLE_TYPES) && $column->isNullable()) {
                $dataType = 'nullable' . ucfirst($dataType);
            }

            $column_definition = self::INDENT;
            if ($dataType === 'bigIncrements') {
                $column_definition .= '$table->id(';
            } elseif ($dataType === 'rememberToken') {
                $column_definition .= '$table->rememberToken(';
            } else {
                $column_definition .= '$table->' . $dataType . "('{$column->name()}'";
            }

            $columnAttributes = $column->attributes();

            if (in_array($dataType, self::INTEGER_TYPES)) {
                $columnAttributes = array_filter(
                    $columnAttributes,
                    fn ($columnAttribute) => !is_numeric($columnAttribute),
                );
            }

            if (!empty($columnAttributes) && !$this->isIdOrUuid($column->dataType())) {
                $column_definition .= ', ';

                if (in_array($column->dataType(), ['set', 'enum'])) {
                    $column_definition .= json_encode($columnAttributes);
                } else {
                    $column_definition .= implode(', ', $columnAttributes);
                }
            }

            $column_definition .= ')';

            $modifiers = $column->modifiers();

            $foreign = '';
            $foreign_modifier = $column->isForeignKey();

            if ($this->shouldAddForeignKeyConstraint($column)) {
                $this->hasForeignKeyConstraints = true;
                $foreign = $this->buildForeignKey(
                    $column->name(),
                    $foreign_modifier === 'foreign' ? null : $foreign_modifier,
                    $column->dataType(),
                    $columnAttributes,
                    $column->modifiers()
                );

                if ($this->isIdOrUuid($column->dataType())) {
                    $column_definition = $foreign;
                    $foreign = '';
                }

                // TODO: unset the proper modifier
                $modifiers = collect($modifiers)->reject(
                    fn ($modifier) => (is_array($modifier) && key($modifier) === 'foreign')
                    || (is_array($modifier) && key($modifier) === 'onDelete')
                    || (is_array($modifier) && key($modifier) === 'onUpdate')
                    || $modifier === 'foreign'
                    || ($modifier === 'nullable' && $this->isIdOrUuid($column->dataType()))
                );
            }

            foreach ($modifiers as $modifier) {
                if (is_array($modifier)) {
                    $modifierKey = key($modifier);
                    $modifierValue = addslashes(current($modifier));
                    if ($modifierKey === 'default' && ($modifierValue === 'null' || $dataType === 'boolean' || $this->isNumericDefault($dataType, $modifierValue))) {
                        $column_definition .= sprintf('->%s(%s)', $modifierKey, $modifierValue);
                    } else {
                        $column_definition .= sprintf("->%s('%s')", $modifierKey, $modifierValue);
                    }
                } elseif ($modifier === 'unsigned' && Str::startsWith($dataType, 'unsigned')) {
                    continue;
                } elseif ($modifier === 'nullable' && Str::startsWith($dataType, 'nullable')) {
                    continue;
                } else {
                    $column_definition .= '->' . $modifier . '()';
                }
            }

            $column_definition .= ';' . PHP_EOL;
            if (!empty($foreign)) {
                $column_definition .= $foreign . ';' . PHP_EOL;
            }
            $definition .= $column_definition;
        }

        $relationships = $model->relationships();

        if (array_key_exists('morphTo', $relationships)) {
            foreach ($relationships['morphTo'] as $morphTo) {
                $definition .= self::INDENT . sprintf('$table->morphs(\'%s\');', Str::lower($morphTo)) . PHP_EOL;
            }
        }

        foreach ($model->indexes() as $index) {
            $index_definition = self::INDENT;
            $index_definition .= '$table->' . $index->type();
            if (count($index->columns()) > 1) {
                $index_definition .= "(['" . implode("', '", $index->columns()) . "']);" . PHP_EOL;
            } else {
                $index_definition .= "('{$index->columns()[0]}');" . PHP_EOL;
            }
            $definition .= $index_definition;
        }
        if ($model->usesTimestamps()) {
            $definition .= self::INDENT . '$table->' . $model->timestampsDataType() . '();' . PHP_EOL;
        }

        if ($model->usesSoftDeletes()) {
            $definition .= self::INDENT . '$table->' . $model->softDeletesDataType() . '();' . PHP_EOL;
        }

        return trim($definition);
    }

    protected function buildPivotTableDefinition(array $segments)
    {
        $definition = '';

        foreach ($segments as $segment) {
            $column = Str::before(Str::snake($segment), ':');
            $references = 'id';
            $on = Str::plural($column);
            $foreign = Str::singular($column) . '_' . $references;

            if (config('blueprint.use_constraints')) {
                $this->hasForeignKeyConstraints = true;
                $definition .= $this->buildForeignKey($foreign, $on, 'id') . ';' . PHP_EOL;
            } else {
                $definition .= self::INDENT . '$table->foreignId(\'' . $foreign . '\');' . PHP_EOL;
            }
        }

        return trim($definition);
    }

    protected function buildPolyTableDefinition(string $parentTable)
    {
        $definition = '';

        $references = 'id';
        $on = Str::lower(Str::plural($parentTable));
        $foreign = Str::lower(Str::singular($parentTable)) . '_' . $references;

        if (config('blueprint.use_constraints')) {
            $this->hasForeignKeyConstraints = true;
            $definition .= $this->buildForeignKey($foreign, $on, 'id') . ';' . PHP_EOL;
        } else {
            $definition .= self::INDENT . '$table->foreignId(\'' . $foreign . '\');' . PHP_EOL;
        }

        $definition .= self::INDENT . sprintf('$table->morphs(\'%s\');', Str::lower(Str::singular($parentTable) . 'able')) . PHP_EOL;

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
        } elseif (Str::contains($on, '\\')) {
            $table = Str::lower(Str::plural(Str::afterLast($on, '\\')));
            $column = Str::afterLast($column_name, '_');
        } else {
            $table = Str::plural($on);
            $column = Str::afterLast($column_name, '_');
        }

        if ($this->isIdOrUuid($type) && !empty($attributes)) {
            $table = Str::lower(Str::plural($attributes[0]));
        }

        $on_delete_suffix = $on_update_suffix = null;
        $on_delete_clause = collect($modifiers)->firstWhere('onDelete');
        if (config('blueprint.use_constraints') || $on_delete_clause) {
            $on_delete_clause = $on_delete_clause ? $on_delete_clause['onDelete'] : config('blueprint.on_delete', 'cascade');
            $on_delete_suffix = self::ON_DELETE_CLAUSES[$on_delete_clause];
        }

        $on_update_clause = collect($modifiers)->firstWhere('onUpdate');
        if (config('blueprint.use_constraints') || $on_update_clause) {
            $on_update_clause = $on_update_clause ? $on_update_clause['onUpdate'] : config('blueprint.on_update', 'cascade');
            $on_update_suffix = self::ON_UPDATE_CLAUSES[$on_update_clause];
        }

        if ($this->isIdOrUuid($type)) {
            if ($type === 'uuid') {
                $method = 'foreignUuid';
            } else {
                $method = 'foreignId';
            }

            $prefix = in_array('nullable', $modifiers)
                ? '$table->' . "{$method}('{$column_name}')->nullable()"
                : '$table->' . "{$method}('{$column_name}')";

            if ($on_delete_clause === 'cascade') {
                $on_delete_suffix = '->cascadeOnDelete()';
            }
            if ($on_update_clause === 'cascade') {
                $on_update_suffix = '->cascadeOnUpdate()';
            }

            if ($column_name === Str::singular($table) . '_' . $column) {
                return self::INDENT . "{$prefix}->constrained(){$on_delete_suffix}{$on_update_suffix}";
            }
            if ($column === 'id') {
                return self::INDENT . "{$prefix}->constrained('{$table}'){$on_delete_suffix}{$on_update_suffix}";
            }

            return self::INDENT . "{$prefix}->constrained('{$table}', '{$column}'){$on_delete_suffix}{$on_update_suffix}";
        }

        return self::INDENT . '$table->foreign' . "('{$column_name}')->references('{$column}')->on('{$table}'){$on_delete_suffix}{$on_update_suffix}";
    }

    protected function disableForeignKeyConstraints($stub): string
    {
        $stub = str_replace('Schema::create(', 'Schema::disableForeignKeyConstraints();' . PHP_EOL . PHP_EOL . str_pad(' ', 8) . 'Schema::create(', $stub);

        $stub = str_replace('});', '});' . PHP_EOL . PHP_EOL . str_pad(' ', 8) . 'Schema::enableForeignKeyConstraints();', $stub);

        return $stub;
    }

    protected function getClassName(Model $model)
    {
        return 'Create' . Str::studly($model->tableName()) . 'Table';
    }

    protected function getTablePath($tableName, Carbon $timestamp, $overwrite = false)
    {
        $dir = 'database/migrations/';
        $name = '_create_' . $tableName . '_table.php';

        if ($overwrite) {
            $migrations = collect($this->filesystem->files($dir))
                ->filter(fn (SplFileInfo $file) => str_contains($file->getFilename(), $name))
                ->sort();

            if ($migrations->isNotEmpty()) {
                $migration = $migrations->first()->getPathname();

                $migrations->diff($migration)
                    ->each(function (SplFileInfo $file) {
                        $path = $file->getPathname();
                        $this->filesystem->delete($path);
                        $this->output['deleted'][] = $path;
                    });

                return $migration;
            }
        }

        return $dir . $timestamp->format('Y_m_d_His') . $name;
    }

    protected function getPivotTableName(array $segments)
    {
        $isCustom = collect($segments)
            ->filter(fn ($segment) => Str::contains($segment, ':'))->first();

        if ($isCustom) {
            $table = Str::after($isCustom, ':');

            return $table;
        }

        $segments = array_map(fn ($name) => Str::snake($name), $segments);
        sort($segments);

        return strtolower(implode('_', $segments));
    }

    protected function getPolyTableName(string $parentTable)
    {
        return Str::plural(Str::lower(Str::singular($parentTable) . 'able'));
    }

    private function shouldAddForeignKeyConstraint(\Blueprint\Models\Column $column)
    {
        if ($column->name() === 'id') {
            return false;
        }

        if ($column->isForeignKey()) {
            return true;
        }

        return config('blueprint.use_constraints')
            && ($this->isIdOrUuid($column->dataType()) && Str::endsWith($column->name(), '_id'));
    }

    protected function isNumericDefault(string $type, string $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (Str::startsWith($type, 'unsigned')) {
            $type = Str::after($type, 'unsigned');
        }

        return collect(self::UNSIGNABLE_TYPES)
            ->contains(fn ($value) => strtolower($value) === strtolower($type));
    }

    protected function isIdOrUuid(string $dataType)
    {
        return in_array($dataType, ['id', 'uuid']);
    }
}
