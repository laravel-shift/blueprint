<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Blueprint\Tree;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class ModelGenerator implements Generator
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    /** @var Tree */
    private $tree;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $output = [];

        if (Blueprint::isLaravel8OrHigher()) {
            $stub = $this->files->stub('model.class.stub');
        } else {
            $stub = $this->files->stub('model.class.no-factory.stub');
        }

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
        if (Blueprint::isLaravel8OrHigher()) {
            $stub = str_replace('{{ namespace }}', $model->fullyQualifiedNamespace(), $stub);
            $stub = str_replace(PHP_EOL . 'class {{ class }}', $this->buildClassPhpDoc($model) . PHP_EOL . 'class {{ class }}', $stub);
            $stub = str_replace('{{ class }}', $model->name(), $stub);

            $body = $this->buildProperties($model);
            $body .= PHP_EOL . PHP_EOL;
            $body .= $this->buildRelationships($model);

            $stub = str_replace('use HasFactory;', 'use HasFactory;' . PHP_EOL . PHP_EOL . '    ' . trim($body), $stub);

            $stub = $this->addTraits($model, $stub);
        } else {
            $stub = str_replace('{{ namespace }}', $model->fullyQualifiedNamespace(), $stub);
            $stub = str_replace('{{ class }}', $model->name(), $stub);
            $stub = str_replace('{{ PHPDoc }}', $this->buildClassPhpDoc($model), $stub);

            $body = $this->buildProperties($model);
            $body .= PHP_EOL . PHP_EOL;
            $body .= $this->buildRelationships($model);

            $stub = str_replace('{{ body }}', trim($body), $stub);
            $stub = $this->addTraits($model, $stub);
        }

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
            if ($column->dataType() === 'morphs') {
                $phpDoc .= ' * @property int $' . $column->name() . '_id';
                $phpDoc .= PHP_EOL;
                $phpDoc .= ' * @property string $' . $column->name() . '_type';
                $phpDoc .= PHP_EOL;
            } elseif ($column->dataType() === 'nullableMorphs') {
                $phpDoc .= ' * @property int|null $' . $column->name() . '_id';
                $phpDoc .= PHP_EOL;
                $phpDoc .= ' * @property string|null $' . $column->name() . '_type';
                $phpDoc .= PHP_EOL;
            } elseif ($column->dataType() === 'uuidMorphs') {
                $phpDoc .= ' * @property string $' . $column->name() . '_id';
                $phpDoc .= PHP_EOL;
                $phpDoc .= ' * @property string $' . $column->name() . '_type';
                $phpDoc .= PHP_EOL;
            } elseif ($column->dataType() === 'nullableUuidMorphs') {
                $phpDoc .= ' * @property string|null $' . $column->name() . '_id';
                $phpDoc .= PHP_EOL;
                $phpDoc .= ' * @property string|null $' . $column->name() . '_type';
                $phpDoc .= PHP_EOL;
            } else {
                $phpDoc .= sprintf(' * @property %s $%s', $this->phpDataType($column->dataType()), $column->name());
                $phpDoc .= PHP_EOL;
            }
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
                $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->files->stub('model.fillable.stub'));
            } else {
                $properties .= $this->files->stub('model.fillable.stub');
            }
        }

        $columns = $this->hiddenColumns($model->columns());
        if (! empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->files->stub('model.hidden.stub'));
        }

        $columns = $this->castableColumns($model->columns());
        if (! empty($columns)) {
            $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns), $this->files->stub('model.casts.stub'));
        }

        if (! Blueprint::isLaravel8OrHigher()) {
            $columns = $this->dateColumns($model->columns());
            if (! empty($columns)) {
                $properties .= PHP_EOL . str_replace('[]', $this->pretty_print_array($columns, false), $this->files->stub('model.dates.stub'));
            }
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
                $custom_template = $template;
                $key = null;
                $class = null;
                $relation_options = [];
                $options = [];
                $related_by_namespace = null;

                $column_name = $related_by_namespace = $reference;

                $method_name = Str::beforeLast($this->getMethodNameFromReference($reference), '_id');

                $relationship_string = '$this->%s(%s::class';

                if (Str::contains($reference, ':')) {
                    [$foreign_reference, $options] = explode(':', $reference);

                    $column_name = str_replace('\\', '', lcfirst(ucwords($foreign_reference, '\\')));
                    $method_name = Str::beforeLast($foreign_reference, '_id');

                    $related_by_namespace = $foreign_reference;

                    $options = Str::contains($options, '.') ? explode('.', $options) : [$options];

                }

                if(config('blueprint.relationships_use_model_fqn')) {
                    $fqcn = Str::startsWith($related_by_namespace, '\\') ? $related_by_namespace : '\\'.$related_by_namespace;
                } else {
                    $fqcn = $this->fullyQualifyModelReference($method_name) ?? $model->fullyQualifiedNamespace() . '\\' . $method_name;
                    $fqcn = Str::startsWith($fqcn, '\\') ? $fqcn : '\\'.$fqcn;
                    $method_name = $column_name;
                }

                array_push($relation_options, $type);
                array_push($relation_options, $fqcn);

                if (empty($options)) {
                    if($type === 'morphMany' || $type === 'morphOne') {
                        $options[] = Str::lower(Str::singular($this->getMethodNameFromReference($column_name))) . 'able';
                    }
                }
                foreach($options as $option) {
                    $relationship_string .= ', \'%s\'';
                    array_push($relation_options, $option);
                }

                $relationship_string .= ')';

                if ($type === 'morphTo') {
                    $relationship = sprintf('$this->%s()', $type);
                } else {
                    $relationship = vsprintf($relationship_string, $relation_options);
                }

                if ($type === 'morphTo') {
                    $method_name = Str::lower($method_name);
                } elseif (in_array($type, ['hasMany', 'belongsToMany', 'morphMany'])) {

                    $method_name = config('blueprint.relationships_use_model_fqn') ?
                        Str::plural($this->getMethodNameFromReference($related_by_namespace)) :
                            Str::plural($column_name);
                }

                if (Blueprint::supportsReturnTypeHits()) {
                    $custom_template = str_replace(
                        '{{ method }}()',
                        '{{ method }}(): ' . Str::of('\Illuminate\Database\Eloquent\Relations\\')->append(Str::studly($type)),
                        $custom_template
                    );
                }
                $method = str_replace('{{ method }}', Str::camel($this->getMethodNameFromReference($method_name)), $custom_template);
                $method = str_replace('null', $relationship, $method);

                $phpDoc = str_replace('{{ namespacedReturnClass }}', '\Illuminate\Database\Eloquent\Relations\\' . Str::ucfirst($type), $commentTemplate);

                $methods .= PHP_EOL . $phpDoc . $method;
            }
        }

        return $methods;
    }

    private function getMethodNameFromReference($reference) {

        if(config('blueprint.relationships_use_model_fqn')) {
            return Str::of($reference)->afterLast('\\')->__toString();
        }

        return $reference;
    }

    protected function getPath(Model $model)
    {
        $path = str_replace('\\', '/', Blueprint::relativeNamespace($model->fullyQualifiedClassName()));

        return Blueprint::appPath() . '/' . $path . '.php';
    }

    protected function addTraits(Model $model, $stub)
    {
        if (!$model->usesSoftDeletes()) {
            return $stub;
        }

        $stub = str_replace('use Illuminate\\Database\\Eloquent\\Model;', 'use Illuminate\\Database\\Eloquent\\Model;' . PHP_EOL . 'use Illuminate\\Database\\Eloquent\\SoftDeletes;', $stub);
        if (Blueprint::isLaravel8OrHigher()) {
            $stub = Str::replaceFirst('use HasFactory', 'use HasFactory, SoftDeletes', $stub);
        } else {
            $stub = Str::replaceFirst('{', '{' . PHP_EOL . '    use SoftDeletes;' . PHP_EOL, $stub);
        }

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
        if (Blueprint::isLaravel8OrHigher()) {
            if ($column->dataType() === 'date') {
                return 'date';
            }

            if (stripos($column->dataType(), 'datetime') !== false) {
                return 'datetime';
            }

            if (stripos($column->dataType(), 'timestamp') !== false) {
                return 'timestamp';
            }
        }

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

    private function fullyQualifyModelReference(string $model_name)
    {
        // TODO: get model_name from tree.
        // If not found, assume parallel namespace as controller.
        // Use respond-statement.php as test case.

        /** @var \Blueprint\Models\Model $model */
        $model = $this->tree->modelForContext($model_name);

        if (isset($model)) {
            return $model->fullyQualifiedClassName();
        }

        return null;
    }
}
