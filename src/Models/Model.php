<?php

namespace Blueprint\Models;

use Illuminate\Support\Str;

class Model
{
    private $name;
    private $namespace;
    private $timestamps = 'timestamps';
    private $softDeletes = false;
    private $columns = [];

    /**
     * @param $name
     */
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

    public function primaryKey()
    {
        return 'id';
    }

    public function tableName()
    {
        return Str::snake(Str::pluralStudly($this->name));
    }

    public function timestampsDataType(): string
    {
        if (strtolower($this->timestamps) === 'timestampstz') {
            return 'timestampsTz';
        }
        return 'timestamps';
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
        $this->timestamps = $withTimezone ? 'timestampstz' : 'timestamps';
    }

    public function softDeletesDataType(): string
    {
        if(strtolower($this->softDeletes) === 'softdeletestz') {
            return 'softDeletesTz';
        }
        return 'softDeletes';
    }

    public function usesSoftDeletes(): bool
    {
        return $this->softDeletes !== false;
    }

    public function enableSoftDeletes(bool $withTimezone = false)
    {
        $this->softDeletes = $withTimezone ? 'softdeletestz' : 'softdeletes';
    }

    public function hasColumn(string $name)
    {
        return isset($this->columns[$name]);
    }

    public function column(string $name)
    {
        return $this->columns[$name];
    }
}