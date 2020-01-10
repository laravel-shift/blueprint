<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Illuminate\Support\Str;

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

        /** @var \Blueprint\Models\Model $model */
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
        $stub = str_replace('DummyNamespace', $model->fullyQualifiedNamespace(), $stub);
        $stub = str_replace('DummyClass', $model->name(), $stub);

        $body = $this->buildProperties($model);
        $body .= PHP_EOL . PHP_EOL;
        $body .= $this->buildRelationships($model);

        $stub = str_replace('// ...', trim($body), $stub);
        $stub = $this->addTraits($model, $stub);

        return $stub;
    }

    private function buildProperties(Model $model)
    {
        $properties = '';

        $columns = $this->fillableColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->getStub('fillable'));
        } else {
            $properties .= $this->getStub('fillable');
        }

        $columns = $this->castableColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns), $this->getStub('casts'));
        }

        $columns = $this->dateColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->getStub('dates'));
        }

        return trim($properties);
    }

    private function buildRelationships(Model $model)
    {
        $columns = array_filter($model->columns(), function (Column $column) {
            return $column->name() !== 'id' && $column->dataType() === 'id';
        });

        if (empty($columns)) {
            return '';
        }

        $methods = '';
        $template = $this->getStub('method');

        /** @var Column $column */
        foreach ($columns as $column) {
            $name = Str::beforeLast($column->name(), '_id');
            $class = Str::studly($column->attributes()[0] ?? $name);
            $relationship = sprintf("\$this->belongsTo(%s::class)", '\\' . $model->fullyQualifiedNamespace() . '\\' . $class);

            $method = str_replace('DummyName', Str::camel($name), $template);
            $method = str_replace('null', $relationship, $method);

            $methods .= PHP_EOL . $method;
        }

        return $methods;
    }

    protected function getPath(Model $model)
    {
        $path = str_replace('\\', '/', Blueprint::relativeNamespace($model->fullyQualifiedClassName()));

        return config('blueprint.app_path') . '/' . $path . '.php';
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

    private function getStub(string $stub)
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
        $stub = preg_replace('/^\\{$/m', '{' . PHP_EOL . '    use SoftDeletes;' . PHP_EOL, $stub);

        return $stub;
    }
}
