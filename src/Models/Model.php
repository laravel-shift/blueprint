<?php

namespace Blueprint\Models;

use Blueprint\Contracts\Model as BlueprintModel;
use Illuminate\Support\Str;

class Model implements BlueprintModel
{
    private string $name;

    private string $namespace;

    private bool $pivot = false;

    private string|bool $primaryKey = 'id';

    private string|bool $timestamps = 'timestamps';

    private string|bool $softDeletes = false;

    private string $table;

    private array $columns = [];

    private array $relationships = [];

    private array $pivotTables = [];

    private array $polymorphicManyToManyTables = [];

    private array $indexes = [];

    public function __construct($name)
    {
        $this->name = class_basename($name);
        $this->namespace = trim(implode('\\', array_slice(explode('\\', str_replace('/', '\\', $name)), 0, -1)), '\\');
    }

    public function name(): string
    {
        return Str::studly($this->name);
    }

    public function namespace(): string
    {
        if (empty($this->namespace)) {
            return '';
        }

        return $this->namespace;
    }

    public function fullyQualifiedNamespace(): string
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

    public function fullyQualifiedClassName(): string
    {
        return $this->fullyQualifiedNamespace() . '\\' . $this->name;
    }

    public function addColumn(Column $column): void
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

    public function primaryKey(): string
    {
        return $this->primaryKey;
    }

    public function usesPrimaryKey(): bool
    {
        return $this->primaryKey !== false;
    }

    public function usesUuids(): bool
    {
        return $this->usesPrimaryKey() && $this->columns[$this->primaryKey]->dataType() === 'uuid';
    }

    public function disablePrimaryKey(): void
    {
        $this->primaryKey = false;
    }

    public function isPivot(): bool
    {
        return $this->pivot;
    }

    public function setPivot(): void
    {
        $this->pivot = true;
    }

    public function usesCustomTableName(): bool
    {
        return isset($this->table);
    }

    public function tableName(): string
    {
        return $this->table ?? Str::snake(Str::pluralStudly($this->name));
    }

    public function setTableName($name): void
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

    public function disableTimestamps(): void
    {
        $this->timestamps = false;
    }

    public function enableTimestamps(bool $withTimezone = false): void
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

    public function enableSoftDeletes(bool $withTimezone = false): void
    {
        $this->softDeletes = $withTimezone ? 'softDeletesTz' : 'softDeletes';
    }

    public function hasColumn(string $name): bool
    {
        return isset($this->columns[$name]);
    }

    public function column(string $name)
    {
        return $this->columns[$name];
    }

    public function addRelationship(string $type, string $reference): void
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

    public function addPolymorphicManyToManyTable(string $reference): void
    {
        $this->polymorphicManyToManyTables[] = class_basename($reference);
    }

    public function addPivotTable(string $reference): void
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

    public function addIndex(Index $index): void
    {
        $this->indexes[] = $index;
    }

    public function pivotTables(): array
    {
        return $this->pivotTables;
    }

    public function polymorphicManyToManyTables(): array
    {
        return $this->polymorphicManyToManyTables;
    }
}
