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

        $stub = $this->files->stub('model/class.stub');

        /** @var \Blueprint\Models\Model $model */
        foreach ($tree['models'] as $model) {
            $path = $this->getPath($model);

            if (!$this->files->exists(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0755, true);
            }

            $this->files->put($path, $this->populateStub($stub, $model));

            $output['created'][] = $path;
        }

        return $output;
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('DummyNamespace', $model->fullyQualifiedNamespace(), $stub);
        $stub = str_replace('DummyClass', $model->name(), $stub);
        $stub = str_replace('/** DummyPHPDocClass **/', $this->buildClassPhpDoc($model), $stub);

        $body = $this->buildProperties($model);
        $body .= PHP_EOL . PHP_EOL;
        $body .= $this->buildRelationships($model);

        $stub = str_replace('// ...', trim($body), $stub);
        $stub = $this->addTraits($model, $stub);

        return $stub;
    }

    private function buildClassPhpDoc(Model $model)
    {
        if (!config('blueprint.generate_phpdocs')) {
            return '';
        }

        $phpDoc = PHP_EOL;
        $phpDoc .= '/**';
        $phpDoc .= PHP_EOL;
        /** @var Column $column */
        foreach ($model->columns() as $column) {
            $phpDoc .= sprintf(' * @property %s $%s', $this->phpDataType($column->dataType()), $column->name());
            $phpDoc .= PHP_EOL;
        }

        if ($model->usesSoftDeletes()) {
            $phpDoc .= ' * @property \Carbon\Carbon $deleted_at';
            $phpDoc .= PHP_EOL;
        }

        if ($model->usesTimestamps()) {
            $phpDoc .= ' * @property \Carbon\Carbon $created_at';
            $phpDoc .= PHP_EOL;
            $phpDoc .= ' * @property \Carbon\Carbon $updated_at';
            $phpDoc .= PHP_EOL;
        }

        $phpDoc .= ' */';

        return $phpDoc;
    }

    private function buildProperties(Model $model)
    {
        $properties = '';

        $columns = $this->fillableColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->files->stub('model/fillable.stub'));
        } else {
            $properties .= $this->files->stub('model/fillable.stub');
        }

        $columns = $this->hiddenColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->files->stub('model/hidden.stub'));
        }

        $columns = $this->castableColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns), $this->files->stub('model/casts.stub'));
        }

        $columns = $this->dateColumns($model->columns());
        if (!empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->files->stub('model/dates.stub'));
        }

        return trim($properties);
    }

    private function buildRelationships(Model $model)
    {
        $methods = '';
        $template = $this->files->stub('model/method.stub');

        foreach ($model->relationships() as $type => $references) {
            foreach ($references as $reference) {
                if (Str::contains($reference, ':')) {
                    [$class, $name] = explode(':', $reference);
                } else {
                    $name = $reference;
                    $class = null;
                }

                $name = Str::beforeLast($name, '_id');
                $class = Str::studly($class ?? $name);
                $relationship = sprintf("\$this->%s(%s::class)", $type, '\\' . $model->fullyQualifiedNamespace() . '\\' . $class);

                $method_name = $type === 'hasMany' ? Str::plural($name) : $name;
                $method = str_replace('DummyName', Str::camel($method_name), $template);
                $method = str_replace('null', $relationship, $method);
                $methods .= PHP_EOL . $method;
            }
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
            'updated_at',
        ]);
    }

    private function hiddenColumns(array $columns)
    {
        return array_intersect(array_keys($columns), [
            'password',
            'remember_token',
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

        if ($column->dataType() === 'json') {
            return 'array';
        }

        return null;
    }

    private function pretty_print_array(array $data, $assoc = true)
    {
        $output = var_export($data, true);
        $output = preg_replace('/^\s+/m', '        ', $output);
        $output = preg_replace(['/^array\s\(/', '/\)$/'], ['[', '    ]'], $output);

        if (!$assoc) {
            $output = preg_replace('/^(\s+)[^=]+=>\s+/m', '$1', $output);
        }

        return trim($output);
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

    private function phpDataType(string $dataType)
    {
        static $php_data_types = [
            'id' => 'int',
            'uuid' => 'string',
            'bigincrements' => 'int',
            'biginteger' => 'int',
            'boolean' => 'bool',
            'date' => '\Carbon\Carbon',
            'datetime' => '\Carbon\Carbon',
            'datetimetz' => '\Carbon\Carbon',
            'decimal' => 'float',
            'double' => 'double',
            'float' => 'float',
            'increments' => 'int',
            'integer' => 'int',
            'mediumincrements' => 'int',
            'mediuminteger' => 'int',
            'nullabletimestamps' => '\Carbon\Carbon',
            'smallincrements' => 'int',
            'smallinteger' => 'int',
            'softdeletes' => '\Carbon\Carbon',
            'softdeletestz' => '\Carbon\Carbon',
            'time' => '\Carbon\Carbon',
            'timetz' => '\Carbon\Carbon',
            'timestamp' => '\Carbon\Carbon',
            'timestamptz' => '\Carbon\Carbon',
            'timestamps' => '\Carbon\Carbon',
            'timestampstz' => '\Carbon\Carbon',
            'tinyincrements' => 'integer',
            'tinyinteger' => 'int',
            'unsignedbiginteger' => 'int',
            'unsigneddecimal' => 'float',
            'unsignedinteger' => 'int',
            'unsignedmediuminteger' => 'int',
            'unsignedsmallinteger' => 'int',
            'unsignedtinyinteger' => 'int',
            'year' => 'int',
        ];

        return $php_data_types[strtolower($dataType)] ?? 'string';
    }
}
