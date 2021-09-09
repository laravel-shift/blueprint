<?php

namespace Blueprint;

use Doctrine\DBAL\Types\Type;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;

class Tracer
{
    /** @var Filesystem */
    private $filesystem;

    public function execute(Blueprint $blueprint, Filesystem $filesystem, array $paths = null): array
    {
        $this->filesystem = $filesystem;

        if (empty($paths)) {
            $paths = [Blueprint::appPath()];

            if (config('blueprint.models_namespace')) {
                $paths[0] .= '/'.str_replace('\\', '/', config('blueprint.models_namespace'));
            }
        }

        $definitions = [];
        foreach ($this->appClasses($paths) as $class) {
            $model = $this->loadModel($class);
            if (is_null($model)) {
                continue;
            }

            $definitions[$this->relativeClassName($model)] = $this->translateColumns($this->mapColumns($this->extractColumns($model)));
        }

        if (empty($definitions)) {
            return $definitions;
        }

        $cache = [];
        if ($filesystem->exists('.blueprint')) {
            $cache = $blueprint->parse($filesystem->get('.blueprint'));
        }

        $cache['models'] = $definitions;

        $filesystem->put('.blueprint', $blueprint->dump($cache));

        return $definitions;
    }

    private function appClasses($paths)
    {
        $classes = [];
        foreach ($paths as $path) {
            if (!$this->filesystem->exists($path)) {
                continue;
            }

            $classes = array_merge($classes, $this->filesystem->allFiles($path));
        }

        return array_filter(array_map(function (\SplFIleInfo $file) {
            if ($file->getExtension() !== 'php') {
                return;
            }

            $content = $this->filesystem->get($file->getPathName());
            preg_match("/namespace ([\w\\\\]+)/", $content, $namespace);
            preg_match("/class (\w+)/", $content, $class);

            return ($namespace[1] ?? '').'\\'.($class[1] ?? '');
        }, $classes));
    }

    private function loadModel(string $class)
    {
        if (!class_exists($class)) {
            return null;
        }

        $reflectionClass = new \ReflectionClass($class);
        if (
            !$reflectionClass->isSubclassOf(\Illuminate\Database\Eloquent\Model::class) ||
            (class_exists('Jenssegers\Mongodb\Eloquent\Model') &&
                $reflectionClass->isSubclassOf('Jenssegers\Mongodb\Eloquent\Model'))
        ) {
            return null;
        }

        return app($class);
    }

    private function extractColumns(Model $model)
    {
        $table = $model->getConnection()->getTablePrefix() . $model->getTable();
        $schema = $model->getConnection()->getDoctrineSchemaManager();

        if (!Type::hasType('enum')) {
            Type::addType('enum', EnumType::class);
            $databasePlatform = $schema->getDatabasePlatform();
            $databasePlatform->registerDoctrineTypeMapping('enum', 'enum');
        }

        $database = null;
        if (strpos($table, '.')) {
            [$database, $table] = explode('.', $table);
        }

        $columns = $schema->listTableColumns($table, $database);

        $uses_enums = collect($columns)->contains(function ($column) {
            return $column->getType() instanceof \Blueprint\EnumType;
        });

        if ($uses_enums) {
            $definitions = $model->getConnection()->getDoctrineConnection()->fetchAllAssociative($schema->getDatabasePlatform()->getListTableColumnsSQL($table, $database));

            collect($columns)->filter(function ($column) {
                return $column->getType() instanceof \Blueprint\EnumType;
            })->each(function (&$column, $key) use ($definitions) {
                $definition = collect($definitions)->where('Field', $key)->first();

                $column->options = \Blueprint\EnumType::extractOptions($definition['Type']);
            });
        }

        return $columns;
    }

    /**
     * @param \Doctrine\DBAL\Schema\Column[] $columns
     */
    private function mapColumns($columns)
    {
        return collect($columns)
            ->map([self::class, 'columns'])
            ->toArray();
    }

    public static function columns(\Doctrine\DBAL\Schema\Column $column, string $key)
    {
        $attributes = [];

        $type = self::translations($column->getType()->getName());

        if (in_array($type, ['decimal', 'float'])) {
            if ($column->getPrecision()) {
                $type .= ':' . $column->getPrecision();
            }
            if ($column->getScale()) {
                $type .= ',' . $column->getScale();
            }
        } elseif ($type === 'string' && $column->getLength()) {
            if ($column->getLength() !== 255) {
                $type .= ':' . $column->getLength();
            }
        } elseif ($type === 'text') {
            if ($column->getLength() > 65535) {
                $type = 'longtext';
            }
        } elseif ($type === 'enum' && !empty($column->options)) {
            $type .= ':' . implode(',', $column->options);
        }

        // TODO: guid/uuid

        $attributes[] = $type;

        if ($column->getUnsigned()) {
            $attributes[] = 'unsigned';
        }

        if (!$column->getNotnull()) {
            $attributes[] = 'nullable';
        }

        if ($column->getAutoincrement()) {
            $attributes[] = 'autoincrement';
        }

        if (!is_null($column->getDefault())) {
            $attributes[] = 'default:' . $column->getDefault();
        }

        return implode(' ', $attributes);
    }

    private static function translations(string $type)
    {
        static $mappings = [
            'array' => 'string',
            'bigint' => 'biginteger',
            'binary' => 'binary',
            'blob' => 'binary',
            'boolean' => 'boolean',
            'date' => 'date',
            'date_immutable' => 'date',
            'dateinterval' => 'date',
            'datetime' => 'datetime',
            'datetime_immutable' => 'datetime',
            'datetimetz' => 'datetimetz',
            'datetimetz_immutable' => 'datetimetz',
            'decimal' => 'decimal',
            'enum' => 'enum',
            'float' => 'float',
            'guid' => 'string',
            'integer' => 'integer',
            'json' => 'json',
            'object' => 'string',
            'simple_array' => 'string',
            'smallint' => 'smallinteger',
            'string' => 'string',
            'text' => 'text',
            'time' => 'time',
            'time_immutable' => 'time',
        ];

        return $mappings[$type] ?? 'string';
    }

    private function translateColumns(array $columns)
    {
        if (isset($columns['id']) && strpos($columns['id'], 'autoincrement') !== false && strpos($columns['id'], 'integer') !== false) {
            unset($columns['id']);
        }

        if (isset($columns[Model::CREATED_AT]) && isset($columns[Model::UPDATED_AT])) {
            if (strpos($columns[Model::CREATED_AT], 'datetimetz') !== false) {
                $columns['timestampstz'] = 'timestampsTz';
            }

            unset($columns[Model::CREATED_AT]);
            unset($columns[Model::UPDATED_AT]);
        }

        if (isset($columns['deleted_at'])) {
            if (strpos($columns['deleted_at'], 'datetimetz') !== false) {
                $columns['softdeletestz'] = 'softDeletesTz';
            }

            unset($columns['deleted_at']);
        }

        return $columns;
    }

    private function relativeClassName($model)
    {
        $name = Blueprint::relativeNamespace(get_class($model));

        return ltrim(str_replace(config('blueprint.models_namespace'), '', $name), '\\');
    }
}
