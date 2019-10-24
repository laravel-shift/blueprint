<?php

namespace Blueprint\Generators;

use Blueprint\Column;
use Blueprint\Model;

class ModelGenerator
{
    public function output(array $tree)
    {
        // TODO: what if changing an existing model
        $stub = file_get_contents('stubs/model/class.stub');

        /** @var \Blueprint\Model $model */
        foreach ($tree['models'] as $model) {
            file_put_contents(
                $this->getPath($model),
                $this->populateStub($stub, $model)
            );
        }
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('DummyNamespace', 'App', $stub);
        $stub = str_replace('DummyClass', $model->name(), $stub);
        $stub = str_replace('// properties...', $this->buildProperties($model), $stub);

        return $stub;
    }

    private function buildProperties(Model $model)
    {
        $properties = '';

        $property = $this->fillableColumns($model->columns());
        if (!empty($property)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($property, false), $this->propertyStub('fillable'));
        }

        $property = $this->castableColumns($model->columns());
        if (!empty($property)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($property), $this->propertyStub('casts'));
        }

        $property = $this->dateColumns($model->columns());
        if (!empty($property)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($property, false), $this->propertyStub('dates'));
        }

        return trim($properties);
    }

    protected function getPath(Model $model)
    {
        return 'build/' . $model->name() . '.php';
    }

    private function fillableColumns(array $columns)
    {
        return array_diff(array_keys($columns), [
            'id',
            'password',
            'deleted_at',
            'created_at',
            'updated_at'
        ]);
    }

    private function castableColumns(array $columns)
    {
        return array_filter(array_map(
            function (Column $column) {
                return $this->castForColumn($column);
            }, $columns));
    }

    private function dateColumns(array $columns)
    {
        return array_map(
            function (Column $column) {
                return $column->name();
            },
            array_filter($columns, function (Column $column) {
                return stripos($column->dataType(), 'datetime') !== false
                    || stripos($column->dataType(), 'timestamp') !== false;
            }));
    }

    private function castForColumn(Column $column)
    {
        if (stripos($column->dataType(), 'integer') || $column->dataType() === 'id') {
            return 'integer';
        }

        if (in_array($column->dataType(), ['boolean', 'double', 'float'])) {
            return strtolower($column->dataType());
        }

        if (in_array($column->dataType(), ['decimal', 'unsignedDecimal'])) {
            if ($column->attributes()) {
                return 'decimal:' . $column->attributes()[1];
            }

            return 'decimal';
        }

        return null;
    }

    private function pretty_print_array(array $data, $assoc = true)
    {
        $output = var_export($data, true);
        $output = preg_replace(['/^array\s\(/', "/\)$/"], ['[', ']'], $output);

        if (!$assoc) {
            $output = preg_replace('/^(\s+)[^=]+=>\s+/m', '$1', $output);
        }

        return $output;
    }

    private function propertyStub(string $stub)
    {
        static $stubs = [];

        if (empty($stubs[$stub])) {
            $stubs[$stub] = file_get_contents('stubs/model/'. $stub .'.stub');
        }

        return $stubs[$stub];
    }
}