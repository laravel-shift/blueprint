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

    protected array $types = ['models'];

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

    protected function populateStub(string $stub, Model $model): string
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

    protected function buildClassPhpDoc(Model $model): string
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
            } elseif ($column->dataType() === 'ulidMorphs') {
                $phpDoc .= ' * @property string $' . $column->name() . '_id';
                $phpDoc .= PHP_EOL;
                $phpDoc .= ' * @property string $' . $column->name() . '_type';
                $phpDoc .= PHP_EOL;
            } elseif ($column->dataType() === 'nullableUlidMorphs') {
                $phpDoc .= ' * @property string|null $' . $column->name() . '_id';
                $phpDoc .= PHP_EOL;
                $phpDoc .= ' * @property string|null $' . $column->name() . '_type';
                $phpDoc .= PHP_EOL;
            } elseif ($column->dataType() === 'ulidMorphs') {
                $phpDoc .= ' * @property string $' . $column->name() . '_id';
                $phpDoc .= PHP_EOL;
                $phpDoc .= ' * @property string $' . $column->name() . '_type';
                $phpDoc .= PHP_EOL;
            } elseif ($column->dataType() === 'nullableUlidMorphs') {
                $phpDoc .= ' * @property string|null $' . $column->name() . '_id';
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

    private function phpDataType(string $dataType): string
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

    protected function buildProperties(Model $model): string
    {
        $properties = [];

        if ($model->usesCustomDatabaseConnection()) {
            $properties[] = str_replace('{{ name }}', $model->databaseConnection(), $this->filesystem->stub('model.connection.stub'));
        }

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

    protected function fillableColumns(array $columns): array
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

    private function pretty_print_array(array $data, bool $assoc = true): string
    {
        $output = var_export($data, true);
        $output = preg_replace('/^\s+/m', '        ', $output);
        $output = preg_replace(['/^array\s\(/', '/\)$/'], ['[', '    ]'], $output);

        if (!$assoc) {
            $output = preg_replace('/^(\s+)[^=]+=>\s+/m', '$1', $output);
        }

        return trim(str_replace("\n", PHP_EOL, $output));
    }

    protected function hiddenColumns(array $columns): array
    {
        return array_intersect(
            array_keys($columns),
            [
                'password',
                'remember_token',
            ]
        );
    }

    protected function castableColumns(array $columns): array
    {
        return array_filter(
            array_map(
                fn (Column $column) => $this->castForColumn($column),
                $columns
            )
        );
    }

    private function castForColumn(Column $column): ?string
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

        if (stripos($column->dataType(), 'integer') || in_array($column->dataType(), ['id', 'foreign'])) {
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

    protected function buildRelationships(Model $model): string
    {
        $methods = '';
        $template = $this->filesystem->stub('model.method.stub');

        foreach ($model->relationships() as $type => $references) {
            foreach ($references as $reference) {
                $is_model_fqn = false;
                $is_pivot = false;
                $custom_template = $template;
                $key = null;
                $class = null;
                $method_name = '';
                $column_name = is_array($reference) ? $reference['foreignKey'] : $reference;

                if (is_array($reference)) {
                    $foreign_info = $reference;
                    $reference = $foreign_info['foreignKey'];
                    $class = $foreign_info['table'];
                    $key = $foreign_info['reference'];
                }

                if (is_string($reference) && Str::startsWith($reference, '\\')) {
                    $is_model_fqn = true;
                    $fqcn = $reference;
                    $class_name = Str::afterLast($fqcn, '\\');
                    $method_name = Str::camel(Str::beforeLast($class_name, '_id'));
                } else {
                    if (Str::contains($column_name, ':')) {
                        [$model_reference, $method_name] = explode(':', $column_name);
                        $column_name = $model_reference;
                    } else {
                        $method_name = $type === 'belongsTo' && is_string($column_name) && Str::endsWith($column_name, '_id') ? Str::camel(Str::beforeLast($column_name, '_id')) : Str::camel($column_name);
                        if ($type === 'belongsTo') {
                            $method_name = $method_name === 'user' ? $method_name . Str::studly(Str::after($column_name, 'user_id_')) : $method_name;
                        }
                        if ($type === 'hasMany') {
                            $method_name = Str::plural($method_name);
                        }
                    }

                    if (!$is_model_fqn) {
                        $class_name = Str::studly(Str::singular($class ?? Str::beforeLast($column_name, '_id')));
                        $fqcn = $this->fullyQualifyModelReference($class_name) ?? $model->fullyQualifiedNamespace() . '\\' . $class_name;
                    }

                    $fqcn = Str::startsWith($fqcn, '\\') ? $fqcn : '\\' . $fqcn;
                    $fqcn = Str::is($fqcn, "\\{$model->fullyQualifiedNamespace()}\\{$class_name}") ? $class_name : $fqcn;
                }

                if ($type === 'morphTo') {
                    $relationship = sprintf('$this->%s()', $type);
                } elseif (in_array($type, ['morphMany', 'morphOne', 'morphToMany'])) {
                    $relation = Str::lower(Str::singular($column_name)) . 'able';
                    $relationship = sprintf('$this->%s(%s::class, \'%s\')', $type, $fqcn, $relation);
                } elseif ($type === 'morphedByMany') {
                    $relationship = sprintf('$this->%s(%s::class, \'%sable\')', $type, $fqcn, strtolower($model->name()));
                } elseif (!is_null($key)) {
                    $relationship = sprintf('$this->%s(%s::class, \'%s\', \'%s\')', $type, $fqcn, $reference, $key);
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
                        $relationship = sprintf('$this->%s(%s::class, \'%s\')', $type, $fqcn, $reference);
                    }
                    $column_name = $class;
                } else {
                    $relationship = sprintf('$this->%s(%s::class)', $type, $fqcn);
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

    private function fullyQualifyModelReference(string $model_name): ?string
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

    protected function pivotColumns(array $columns, array $relationships): array
    {
        // TODO: ideally restrict to only "belongsTo" columns used for pivot relationship
        return collect($columns)
            ->map(fn ($column) => $column->name())
            ->reject(fn ($column) => in_array($column, ['created_at', 'updated_at']) || in_array($column, $relationships['belongsTo'] ?? []))
            ->all();
    }

    protected function addTraits(Model $model, $stub): string
    {
        $traits = ['HasFactory'];

        if ($model->usesSoftDeletes()) {
            $this->addImport($model, 'Illuminate\\Database\\Eloquent\\SoftDeletes');
            $traits[] = 'SoftDeletes';
        }

        if ($model->usesUlids()) {
            $this->addImport($model, 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids');
            $traits[] = 'HasUlids';
        }

        if ($model->usesUuids()) {
            $this->addImport($model, 'Illuminate\\Database\\Eloquent\\Concerns\\HasUuids');
            $traits[] = 'HasUuids';
        }

        sort($traits);

        return Str::replaceFirst('use HasFactory', 'use ' . implode(', ', $traits), $stub);
    }

    protected function dateColumns(array $columns)
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
}
