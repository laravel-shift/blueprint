<?php

namespace Blueprint\Generators;

use Blueprint\Column;
use Blueprint\Contracts\Generator;
use Blueprint\Model;

class ModelGenerator implements Generator
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->get(STUBS_PATH . '/model/class.stub');

        /** @var \Blueprint\Model $model */
        foreach ($tree['models'] as $model) {
            $path = $this->getPath($model);
            $this->files->put(
                $path,
                $this->populateStub($stub, $model)
            );

            $output['created'][] = $path;
        }

        return $output;
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('DummyNamespace', 'App', $stub);
        $stub = str_replace('DummyClass', $model->name(), $stub);
        $stub = str_replace('// properties...', $this->buildProperties($model), $stub);
        $stub = $this->addTraits($model, $stub);

        return $stub;
    }

    private function buildProperties(Model $model)
    {
        $properties = '';

        $columns = $this->fillableColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->propertyStub('fillable'));
        } else {
            $properties .= $this->propertyStub('fillable');
        }

        $columns = $this->castableColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns), $this->propertyStub('casts'));
        }

        $columns = $this->dateColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->propertyStub('dates'));
        }

        return trim($properties);
    }

    protected function getPath(Model $model)
    {
        return 'app/' . $model->name() . '.php';
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
        $output = preg_replace('/^\s+/m', '        ', $output);
        $output = preg_replace(['/^array\s\(/', "/\)$/"], ['[', '    ]'], $output);

        if (!$assoc) {
            $output = preg_replace('/^(\s+)[^=]+=>\s+/m', '$1', $output);
        }


        return trim($output);
    }

    private function propertyStub(string $stub)
    {
        static $stubs = [];

        if (empty($stubs[$stub])) {
            $stubs[$stub] = $this->files->get(STUBS_PATH . '/model/' . $stub . '.stub');
        }

        return $stubs[$stub];
    }

    private function addTraits(Model $model, $stub)
    {
        if (!$model->usesSoftDeletes()) {
            return $stub;
        }

        $stub = str_replace('use Illuminate\\Database\\Eloquent\\Model;', 'use Illuminate\\Database\\Eloquent\\Model;' . PHP_EOL . 'use Illuminate\\Database\\Eloquent\\SoftDeletes;', $stub);
        $stub = str_replace('{', '{' . PHP_EOL . '    use SoftDeletes;' . PHP_EOL, $stub);

        return $stub;
    }
}
