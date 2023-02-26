<?php

namespace Blueprint\Models;

use Blueprint\Contracts\Model as BlueprintModel;
use Illuminate\Support\Str;

class Model implements BlueprintModel
{
    private $name;

    private $namespace;

    private $pivot = false;

    private $primaryKey = 'id';

    private $timestamps = 'timestamps';

    private $softDeletes = false;

    private $table;

    private $columns = [];

    private $relationships = [];

    private $pivotTables = [];

    private $polymorphicManyToManyTables = [];

    private $indexes = [];

    public function __construct($name)
    {
        $this->name = class_basename($name);
        $this->namespace = trim(implode('\\', array_slice(explode('\\', str_replace('/', '\\', $name)), 0, -1)), '\\');
    }

    public function name(): string
    {
        return Str::studly($this->name);
    }

    public function namespace()
    {
        if (empty($this->namespace)) {
            return '';
        }

        return $this->namespace;
    }

    public function fullyQualifiedNamespace()
    {
        $fqn = config('blueprint.namespace');

        if (config('blueprint.models_namespace')) {
            $fqn .= '\\' . config('blueprint.models_namespace');
        }

        if ($this->namespace) {
            $fqn .= '\\' . $this->namespace;
        }

        return $fqn;
    }

    public function fullyQualifiedClassName()
    {
        return $this->fullyQualifiedNamespace() . '\\' . $this->name;
    }

    public function addColumn(Column $column)
    {
        $this->columns[$column->name()] = $column;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function relationships(): array
    {
        return $this->relationships;
    }

    public function primaryKey()
    {
        return $this->primaryKey;
    }

    public function usesPrimaryKey()
    {
        return $this->primaryKey !== false;
    }

    public function disablePrimaryKey()
    {
        $this->primaryKey = false;
    }

    public function isPivot()
    {
        return $this->pivot;
    }

    public function setPivot()
    {
        $this->pivot = true;
    }

    public function usesCustomTableName()
    {
        return isset($this->table);
    }

    public function tableName()
    {
        return $this->table ?? Str::snake(Str::pluralStudly($this->name));
    }

    public function setTableName($name)
    {
        $this->table = $name;
    }

    public function timestampsDataType(): string
    {
        return $this->timestamps;
    }

    public function usesTimestamps(): bool
    {
        return $this->timestamps !== false;
    }

    public function disableTimestamps()
    {
        $this->timestamps = false;
    }

    public function enableTimestamps(bool $withTimezone = false)
    {
        $this->timestamps = $withTimezone ? 'timestampsTz' : 'timestamps';
    }

    public function softDeletesDataType(): string
    {
        return $this->softDeletes;
    }

    public function usesSoftDeletes(): bool
    {
        return $this->softDeletes !== false;
    }

    public function enableSoftDeletes(bool $withTimezone = false)
    {
        $this->softDeletes = $withTimezone ? 'softDeletesTz' : 'softDeletes';
    }

    public function hasColumn(string $name)
    {
        return isset($this->columns[$name]);
    }

    public function column(string $name)
    {
        return $this->columns[$name];
    }

    public function addRelationship(string $type, string $reference)
    {
        if (!isset($this->relationships[$type])) {
            $this->relationships[$type] = [];
        }

        if ($type === 'belongsToMany') {
            $this->addPivotTable($reference);
        }
        if ($type === 'morphedByMany') {
            $this->addPolymorphicManyToManyTable(Str::studly($this->tableName()));
        }

        $this->relationships[$type][] = $reference;
    }

    public function addPolymorphicManyToManyTable(string $reference)
    {
        $this->polymorphicManyToManyTables[] = class_basename($reference);
    }

    public function addPivotTable(string $reference)
    {
        if (str_contains($reference, ':&')) {
            return;
        }

        $segments = [$this->name(), class_basename($reference)];
        sort($segments);
        $this->pivotTables[] = $segments;
    }

    public function indexes(): array
    {
        return $this->indexes;
    }

    public function addIndex(Index $index)
    {
        $this->indexes[] = $index;
    }

    public function pivotTables(): array
    {
        return $this->pivotTables;
    }

    public function polymorphicManyToManyTables()
    {
        return $this->polymorphicManyToManyTables;
    }
}
