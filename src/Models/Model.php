<?php

namespace Blueprint\Models;

use Illuminate\Support\Str;

class Model
{
    private $name;
    private $timestamps = 'timestamps';
    private $softDeletes = false;
    private $columns = [];

    /**
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return Str::studly($this->name);
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
}