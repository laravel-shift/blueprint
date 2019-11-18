<?php

namespace Blueprint\Models;

use Illuminate\Support\Str;

class Model
{
    private $name;
    private $timestamps = true;
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

    public function usesTimestamps()
    {
        return $this->timestamps;
    }

    public function disableTimestamps()
    {
        $this->timestamps = false;
    }

    public function primaryKey()
    {

    }

    public function tableName()
    {
        return Str::snake(Str::pluralStudly($this->name));
    }

    public function usesSoftDeletes(): bool
    {
        return $this->softDeletes;
    }

    public function enableSoftDeletes()
    {
        $this->softDeletes = true;
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