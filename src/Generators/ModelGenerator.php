<?php

namespace Blueprint\Generators;

use Blueprint\Concerns\HandlesImports;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Model;
use Blueprint\Tree;
use Illuminate\Support\Str;

class ModelGenerator extends AbstractClassGenerator implements Generator
{
    use HandlesImports;

    protected $types = ['models'];

    public function output(Tree $tree): array
    {
        $this->tree = $tree;
        $stub = $this->filesystem->stub('model.class.stub');

        /**
         * @var \Blueprint\Models\Model $model
         */
        foreach ($tree->models() as $model) {
            $path = $this->getPath($model);

            $this->create($path, $this->populateStub($stub, $model));
        }

        return $this->output;
    }

    private function pivotColumns(array $columns, array $relationships): array
    {
        // TODO: ideally restrict to only "belongsTo" columns used for pivot relationship
        return collect($columns)
            ->map(fn ($column) => $column->name())
            ->reject(fn ($column) => in_array($column, ['created_at', 'updated_at']) || in_array($column, $relationships['belongsTo'] ?? []))
            ->all();
    }

    protected function populateStub(string $stub, Model $model)
    {
        if ($model->isPivot()) {
            $stub = str_replace('class {{ class }} extends Model', 'class {{ class }} extends Pivot', $stub);
            $this->addImport($model, 'Illuminate\\Database\\Eloquent\\Relations\\Pivot');
        } else {
            $this->addImport($model, 'Illuminate\\Database\\Eloquent\\Model');
        }

        $stub = str_replace('{{ namespace }}', $model->fullyQualifiedNamespace(), $stub);
        $stub = str_replace(PHP_EOL . 'class {{ class }}', $this->buildClassPhpDoc($model) . PHP_EOL . 'class {{ class }}', $stub);
        $stub = str_replace('{{ class }}', $model->name(), $stub);

        $body = $this->buildProperties($model);
        $body .= PHP_EOL . PHP_EOL;
        $body .= $this->buildRelationships($model);

        $this->addImport($model, 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory');
        $stub = str_replace('use HasFactory;', 'use HasFactory;' . PHP_EOL . PHP_EOL . '    ' . trim($body), $stub);

        $stub = $this->addTraits($model, $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($model), $stub);

        return $stub;
    }

    protected function buildClassPhpDoc(Model $model)
    {
        if (!config('blueprint.generate_phpdocs')) {
            return '';
        }

        $phpDoc = PHP_EOL;
        $phpDoc .= '/**';
        $phpDoc .= PHP_EOL;
        /**
         * @var Column $column
         */
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

        if ($model->usesTimestamps()) {
            $phpDoc .= ' * @property \Carbon\Carbon $created_at';
            $phpDoc .= PHP_EOL;
            $phpDoc .= ' * @property \Carbon\Carbon $updated_at';
            $phpDoc .= PHP_EOL;
        }

        if ($model->usesSoftDeletes()) {
            $phpDoc .= ' * @property \Carbon\Carbon $deleted_at';
            $phpDoc .= PHP_EOL;
        }

        $phpDoc .= ' */';

        return $phpDoc;
    }

    protected function buildProperties(Model $model)
    {
        $properties = [];

        if ($model->usesCustomTableName() || $model->isPivot()) {
            $properties[] = str_replace('{{ name }}', $model->tableName(), $this->filesystem->stub('model.table.stub'));
        }

        if (!$model->usesTimestamps()) {
            $properties[] = $this->filesystem->stub('model.timestamps.stub');
        }

        if ($model->isPivot() && $model->usesPrimaryKey()) {
            $properties[] = $this->filesystem->stub('model.incrementing.stub');
        }

        if (config('blueprint.use_guarded')) {
            $properties[] = $this->filesystem->stub('model.guarded.stub');
        } else {
            $columns = $this->fillableColumns($model->columns());
            if (!empty($columns)) {
                $properties[] = str_replace('[]', $this->pretty_print_array($columns, false), $this->filesystem->stub('model.fillable.stub'));
            } else {
                $properties[] = $this->filesystem->stub('model.fillable.stub');
            }
        }

        $columns = $this->hiddenColumns($model->columns());
        if (!empty($columns)) {
            $properties[] = str_replace('[]', $this->pretty_print_array($columns, false), $this->filesystem->stub('model.hidden.stub'));
        }

        $columns = $this->castableColumns($model->columns());
        if (!empty($columns)) {
            $properties[] = str_replace('[]', $this->pretty_print_array($columns), $this->filesystem->stub('model.casts.stub'));
        }

        return trim(implode(PHP_EOL, array_filter($properties, fn ($property) => !empty(trim($property)))));
    }

    protected function buildRelationships(Model $model)
    {
        $methods = '';
        $template = $this->filesystem->stub('model.method.stub');

        foreach ($model->relationships() as $type => $references) {
            foreach ($references as $reference) {
                $is_model_fqn = Str::startsWith($reference, '\\');
                $is_pivot = false;

                $custom_template = $template;
                $key = null;
                $class = null;

                $column_name = $reference;
                $method_name = $is_model_fqn ? Str::afterLast($reference, '\\') : Str::beforeLast($reference, '_id');

                if (Str::contains($reference, ':')) {
                    [$foreign_reference, $column_name] = explode(':', $reference);

                    if (Str::startsWith($column_name, '&')) {
                        $is_pivot = true;
                        $column_name = Str::after($column_name, '&');
                        $method_name = $column_name;
                    } else {
                        $method_name = Str::beforeLast($column_name, '_id');
                    }

                    if (Str::contains($foreign_reference, '.')) {
                        [$class, $key] = explode('.', $foreign_reference);

                        if ($key === 'id') {
                            $key = null;
                        }
                        $method_name = $is_model_fqn ? Str::lower(Str::afterLast($class, '\\')) : Str::lower($class);
                    } else {
                        $class = $foreign_reference;
                    }
                }

                if ($is_model_fqn) {
                    $fqcn = $class ?? $column_name;
                    $class_name = Str::afterLast($fqcn, '\\');
                } else {
                    $class_name = Str::studly($class ?? $method_name);
                    $fqcn = $this->fullyQualifyModelReference($class_name) ?? $model->fullyQualifiedNamespace() . '\\' . $class_name;
                }

                $fqcn = Str::startsWith($fqcn, '\\') ? $fqcn : '\\' . $fqcn;
                $fqcn = Str::is($fqcn, "\\{$model->fullyQualifiedNamespace()}\\{$class_name}") ? $class_name : $fqcn;

                if ($type === 'morphTo') {
                    $relationship = sprintf('$this->%s()', $type);
                } elseif (in_array($type, ['morphMany', 'morphOne', 'morphToMany'])) {
                    $relation = Str::lower($is_model_fqn ? Str::singular(Str::afterLast($column_name, '\\')) : Str::singular($column_name)) . 'able';
                    $relationship = sprintf('$this->%s(%s::class, \'%s\')', $type, $fqcn, $relation);
                } elseif ($type === 'morphedByMany') {
                    $relationship = sprintf('$this->%s(%s::class, \'%sable\')', $type, $fqcn, strtolower($model->name()));
                } elseif (!is_null($key)) {
                    $relationship = sprintf('$this->%s(%s::class, \'%s\', \'%s\')', $type, $fqcn, $column_name, $key);
                } elseif (!is_null($class) && $type === 'belongsToMany') {
                    if ($is_pivot) {
                        $relationship = sprintf('$this->%s(%s::class)', $type, $fqcn);
                        $relationship .= sprintf('%s->using(%s::class)', PHP_EOL . str_pad(' ', 12), $column_name);
                        $relationship .= sprintf('%s->as(\'%s\')', PHP_EOL . str_pad(' ', 12), Str::snake($column_name));

                        $foreign = $this->tree->modelForContext($column_name, true);
                        $columns = $this->pivotColumns($foreign->columns(), $foreign->relationships());
                        if ($columns) {
                            $relationship .= sprintf('%s->withPivot(\'%s\')', PHP_EOL . str_pad(' ', 12), implode("', '", $columns));
                        }
                        if ($foreign->usesTimestamps()) {
                            $relationship .= sprintf('%s->withTimestamps()', PHP_EOL . str_pad(' ', 12));
                        }
                    } else {
                        $relationship = sprintf('$this->%s(%s::class, \'%s\')', $type, $fqcn, $column_name);
                    }
                    $column_name = $class;
                } else {
                    $relationship = sprintf('$this->%s(%s::class)', $type, $fqcn);
                }

                if ($type === 'morphTo') {
                    $method_name = Str::lower($class_name);
                } elseif (in_array($type, ['hasMany', 'belongsToMany', 'morphMany', 'morphToMany', 'morphedByMany'])) {
                    $method_name = Str::plural($is_model_fqn ? Str::afterLast($column_name, '\\') : $column_name);
                }

                $relationship_type = 'Illuminate\\Database\\Eloquent\\Relations\\' . Str::studly($type === 'morphedByMany' ? 'morphToMany' : $type);
                $this->addImport($model, $relationship_type);
                $custom_template = str_replace(
                    '{{ method }}()',
                    '{{ method }}(): ' . Str::afterLast($relationship_type, '\\'),
                    $custom_template
                );

                $method = str_replace('{{ method }}', Str::camel($method_name), $custom_template);
                $method = str_replace('null', $relationship, $method);

                $methods .= $method . PHP_EOL;
            }
        }

        return $methods;
    }

    protected function addTraits(Model $model, $stub)
    {
        if (!$model->usesSoftDeletes()) {
            return $stub;
        }

        $this->addImport($model, 'Illuminate\\Database\\Eloquent\\SoftDeletes');
        $stub = Str::replaceFirst('use HasFactory', 'use HasFactory, SoftDeletes', $stub);

        return $stub;
    }

    private function fillableColumns(array $columns)
    {
        return array_diff(
            array_keys($columns),
            [
                'id',
                'deleted_at',
                'created_at',
                'updated_at',
                'remember_token',
                'softdeletes',
                'softdeletestz',
            ]
        );
    }

    private function hiddenColumns(array $columns)
    {
        return array_intersect(
            array_keys($columns),
            [
                'password',
                'remember_token',
            ]
        );
    }

    private function castableColumns(array $columns)
    {
        return array_filter(
            array_map(
                fn (Column $column) => $this->castForColumn($column),
                $columns
            )
        );
    }

    private function dateColumns(array $columns)
    {
        return array_map(
            fn (Column $column) => $column->name(),
            array_filter(
                $columns,
                fn (Column $column) => $column->dataType() === 'date'
                    || stripos($column->dataType(), 'datetime') !== false
                    || stripos($column->dataType(), 'timestamp') !== false
            )
        );
    }

    private function castForColumn(Column $column)
    {
        if ($column->dataType() === 'date') {
            return 'date';
        }

        if (stripos($column->dataType(), 'datetime') !== false) {
            return 'datetime';
        }

        if (stripos($column->dataType(), 'timestamp') !== false) {
            return 'timestamp';
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

        if (!$assoc) {
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
            'tinyincrements' => 'int',
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

        /**
         * @var \Blueprint\Models\Model $model
         */
        $model = $this->tree->modelForContext($model_name);

        if (isset($model)) {
            return $model->fullyQualifiedClassName();
        }

        return null;
    }
}
