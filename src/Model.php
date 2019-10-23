<?php

namespace Blueprint;

use Illuminate\Support\Str;

class Model
{
    private $name;
    private $timestamps = true;
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
}