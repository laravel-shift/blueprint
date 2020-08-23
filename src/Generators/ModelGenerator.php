<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Blueprint\Tree;
use Illuminate\Support\Str;

class ModelGenerator implements Generator
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(Tree $tree): array
    {
        $output = [];

        $stub = $this->files->stub('model.class.stub');

        /** @var \Blueprint\Models\Model $model */
        foreach ($tree->models() as $model) {
            $path = $this->getPath($model);

            if (! $this->files->exists(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0755, true);
            }

            $this->files->put($path, $this->populateStub($stub, $model));

            $output['created'][] = $path;
        }

        return $output;
    }

    public function types(): array
    {
        return ['models'];
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('{{ namespace }}', $model->fullyQualifiedNamespace(), $stub);
        $stub = str_replace('{{ class }}', $model->name(), $stub);
        $stub = str_replace('{{ PHPDoc }}', $this->buildClassPhpDoc($model), $stub);

        $body = $this->buildProperties($model);
        $body .= PHP_EOL.PHP_EOL;
        $body .= $this->buildRelationships($model);

        $stub = str_replace('{{ body }}', trim($body), $stub);
        $stub = $this->addTraits($model, $stub);

        return $stub;
    }

    protected function buildClassPhpDoc(Model $model)
    {
        if (! config('blueprint.generate_phpdocs')) {
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

    protected function buildProperties(Model $model)
    {
        $properties = '';

        if (! $model->usesTimestamps()) {
            $properties .= $this->files->stub('model.timestamps.stub');
        }

        if (config('blueprint.use_guarded')) {
            $properties .= $this->files->stub('model.guarded.stub');
        } else {
            $columns = $this->fillableColumns($model->columns());
            if (! empty($columns)) {
                $properties .= PHP_EOL.str_replace('[]', $this->pretty_print_array($columns, false), $this->files->stub('model.fillable.stub'));
            } else {
                $properties .= $this->files->stub('model.fillable.stub');
            }
        }

        $columns = $this->hiddenColumns($model->columns());
        if (! empty($columns)) {
            $properties .= PHP_EOL.str_replace('[]', $this->pretty_print_array($columns, false), $this->files->stub('model.hidden.stub'));
        }

        $columns = $this->castableColumns($model->columns());
        if (! empty($columns)) {
            $properties .= PHP_EOL.str_replace('[]', $this->pretty_print_array($columns), $this->files->stub('model.casts.stub'));
        }

        $columns = $this->dateColumns($model->columns());
        if (! empty($columns)) {
            $properties .= PHP_EOL.str_replace('[]', $this->pretty_print_array($columns, false), $this->files->stub('model.dates.stub'));
        }

        return trim($properties);
    }

    protected function buildRelationships(Model $model)
    {
        $methods = '';
        $template = $this->files->stub('model.method.stub');
        $commentTemplate = '';

        if (config('blueprint.generate_phpdocs')) {
            $commentTemplate = $this->files->stub('model.method.comment.stub');
        }

        foreach ($model->relationships() as $type => $references) {
            foreach ($references as $reference) {
                $key = null;
                $class = null;

                $column_name = $reference;
                $method_name = Str::beforeLast($reference, '_id');

                if (Str::contains($reference, ':')) {
                    [$foreign_reference, $column_name] = explode(':', $reference);
                    $method_name = Str::beforeLast($column_name, '_id');

                    if (Str::contains($foreign_reference, '.')) {
                        [$class, $key] = explode('.', $foreign_reference);

                        if ($key === 'id') {
                            $key = null;
                        } else {
                            $method_name = Str::lower($class);
                        }
                    } else {
                        $class = $foreign_reference;
                    }
                }

                $class_name = Str::studly($class ?? $method_name);

                if ($type === 'morphTo') {
                    $relationship = sprintf('$this->%s()', $type);
                } elseif ($type === 'morphMany' || $type === 'morphOne') {
                    $relation = Str::lower(Str::singular($column_name)).'able';
                    $relationship = sprintf('$this->%s(%s::class, \'%s\')', $type, '\\'.$model->fullyQualifiedNamespace().'\\'.$class_name, $relation);
                } elseif (! is_null($key)) {
                    $relationship = sprintf('$this->%s(%s::class, \'%s\', \'%s\')', $type, '\\'.$model->fullyQualifiedNamespace().'\\'.$class_name, $column_name, $key);
                } elseif (! is_null($class) && $type === 'belongsToMany') {
                    $relationship = sprintf('$this->%s(%s::class, \'%s\')', $type, '\\'.$model->fullyQualifiedNamespace().'\\'.$class_name, $column_name);
                    $column_name = $class;
                } else {
                    $relationship = sprintf('$this->%s(%s::class)', $type, '\\'.$model->fullyQualifiedNamespace().'\\'.$class_name);
                }

                if ($type === 'morphTo') {
                    $method_name = Str::lower($class_name);
                } elseif (in_array($type, ['hasMany', 'belongsToMany', 'morphMany'])) {
                    $method_name = Str::plural($column_name);
                }
                $method = str_replace('{{ method }}', Str::camel($method_name), $template);
                $method = str_replace('null', $relationship, $method);

                $phpDoc = str_replace('{{ namespacedReturnClass }}', '\Illuminate\Database\Eloquent\Relations\\'.Str::ucfirst($type), $commentTemplate);

                $methods .= PHP_EOL.$phpDoc.$method;
            }
        }

        return $methods;
    }

    protected function getPath(Model $model)
    {
        $path = str_replace('\\', '/', Blueprint::relativeNamespace($model->fullyQualifiedClassName()));

        return Blueprint::appPath().'/'.$path.'.php';
    }

    protected function addTraits(Model $model, $stub)
    {
        if (!$model->usesSoftDeletes()) {
            return $stub;
        }

        $stub = str_replace('use Illuminate\\Database\\Eloquent\\Model;', 'use Illuminate\\Database\\Eloquent\\Model;'.PHP_EOL.'use Illuminate\\Database\\Eloquent\\SoftDeletes;', $stub);
        $stub = Str::replaceFirst('{', '{'.PHP_EOL.'    use SoftDeletes;'.PHP_EOL, $stub);

        return $stub;
    }

    private function fillableColumns(array $columns)
    {
        return array_diff(array_keys($columns), [
            'id',
            'deleted_at',
            'created_at',
            'updated_at',
            'remember_token',
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
            },
            $columns
        ));
    }

    private function dateColumns(array $columns)
    {
        return array_map(
            function (Column $column) {
                return $column->name();
            },
            array_filter($columns, function (Column $column) {
                return $column->dataType() === 'date'
                    || stripos($column->dataType(), 'datetime') !== false
                    || stripos($column->dataType(), 'timestamp') !== false;
            })
        );
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
                return 'decimal:'.$column->attributes()[1];
            }

            return 'decimal';
        }

        if ($column->dataType() === 'json') {
            return 'array';
        }
    }

    private function pretty_print_array(array $data, $assoc = true)
    {
        $output = var_export($data, true);
        $output = preg_replace('/^\s+/m', '        ', $output);
        $output = preg_replace(['/^array\s\(/', '/\)$/'], ['[', '    ]'], $output);

        if (! $assoc) {
            $output = preg_replace('/^(\s+)[^=]+=>\s+/m', '$1', $output);
        }

        return trim(str_replace("\n", PHP_EOL, $output));
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
