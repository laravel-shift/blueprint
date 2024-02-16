<?php

namespace Blueprint;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class Tracer
{
    private Filesystem $filesystem;

    public function execute(Blueprint $blueprint, Filesystem $filesystem, ?array $paths = null): array
    {
        $this->filesystem = $filesystem;

        if (empty($paths)) {
            $paths = [Blueprint::appPath()];

            if (config('blueprint.models_namespace')) {
                $paths[0] .= '/' . str_replace('\\', '/', config('blueprint.models_namespace'));
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

    private function appClasses($paths): array
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
                return [];
            }

            $content = $this->filesystem->get($file->getPathName());
            preg_match("/namespace ([\w\\\\]+)/", $content, $namespace);
            preg_match("/class (\w+)/", $content, $class);

            return ($namespace[1] ?? '') . '\\' . ($class[1] ?? '');
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

    private function extractColumns(Model $model): array
    {
        return $model->getConnection()->getSchemaBuilder()->getColumns($model->getTable());
    }

    private function mapColumns(array $columns): array
    {
        return collect($columns)
            ->keyBy('name')
            ->map([self::class, 'columnAttributes'])
            ->toArray();
    }

    public static function columnAttributes(array $column): string
    {
        $attributes = [];

        $type = self::translations($column);

        if (in_array($type, ['decimal', 'float', 'time', 'timetz', 'datetime', 'datetimetz', 'timestamp', 'timestamptz', 'geography', 'geometry'])
            && str_contains($column['type'], '(')) {
            $options = Str::between($column['type'], '(', ')');
            if ($options) {
                $type .= ':' . $options;
            }
        } elseif (in_array($type, ['string', 'char']) && str_contains($column['type'], '(')) {
            $length = Str::between($column['type'], '(', ')');
            if ($length != 255) {
                $type .= ':' . $length;
            }
        } elseif (in_array($type, ['enum', 'set'])) {
            $options = Str::between($column['type'], '(', ')');
            $type .= ':' . $options;
        }

        // TODO: guid/uuid

        $attributes[] = $type;

        if (str_contains($column['type'], 'unsigned')) {
            $attributes[] = 'unsigned';
        }

        if ($column['nullable']) {
            $attributes[] = 'nullable';
        }

        if ($column['auto_increment']) {
            $attributes[] = 'autoincrement';
        }

        if ($column['default']) {
            $attributes[] = 'default:' . $column['default'];
        }

        return implode(' ', $attributes);
    }

    private static function translations(array $column): string
    {
        $type = match ($column['type']) {
            'tinyint(1)', 'bit' => 'boolean',
            'nvarchar(max)' => 'text',
            default => null,
        };

        $type ??= match ($column['type_name']) {
            'bigint', 'int8' => 'biginteger',
            'binary', 'varbinary', 'bytea', 'image', 'blob', 'tinyblob', 'mediumblob', 'longblob' => 'binary',
            // 'bit', 'varbit' => 'bit',
            'boolean', 'bool' => 'boolean',
            'char', 'bpchar', 'nchar' => 'char',
            'date' => 'date',
            'datetime', 'datetime2' => 'datetime',
            'datetimeoffset' => 'datetimetz',
            'decimal', 'numeric' => 'decimal',
            'double', 'float8' => 'double',
            'enum' => 'enum',
            'float', 'real', 'float4' => 'float',
            'geography' => 'geography',
            'geometry', 'geometrycollection', 'linestring', 'multilinestring', 'multipoint', 'multipolygon', 'point', 'polygon' => 'geometry',
            // 'box', 'circle', 'line', 'lseg', 'path' => 'geometry',
            'integer', 'int', 'int4' => 'integer',
            'inet', 'cidr' => 'ipaddress',
            // 'interval' => 'interval',
            'json' => 'json',
            'jsonb' => 'jsonb',
            'longtext' => 'longtext',
            'macaddr', 'macaddr8' => 'macadress',
            'mediumint' => 'mediuminteger',
            'mediumtext' => 'mediumtext',
            // 'money', 'smallmoney' => 'money',
            'set' => 'set',
            'smallint', 'int2' => 'smallinteger',
            'text', 'ntext' => 'text',
            'time' => 'time',
            'timestamp' => 'timestamp',
            'timestamptz' => 'timestamptz',
            'timetz' => 'timetz',
            'tinyint' => 'tinyinteger',
            'tinytext' => 'tinytext',
            'uuid', 'uniqueidentifier' => 'uuid',
            'varchar', 'nvarchar' => 'string',
            // 'xml' => 'xml',
            'year' => 'year',
            default => null,
        };

        return $type ?? 'string';
    }

    private function translateColumns(array $columns): array
    {
        if (isset($columns['id']) && str_contains($columns['id'], 'autoincrement') && str_contains($columns['id'], 'integer')) {
            unset($columns['id']);
        }

        if (isset($columns[Model::CREATED_AT]) && isset($columns[Model::UPDATED_AT])) {
            if (str_contains($columns[Model::CREATED_AT], 'datetimetz')) {
                $columns['timestampstz'] = 'timestampsTz';
            }

            unset($columns[Model::CREATED_AT]);
            unset($columns[Model::UPDATED_AT]);
        }

        if (isset($columns['deleted_at'])) {
            if (str_contains($columns['deleted_at'], 'datetimetz')) {
                $columns['softdeletestz'] = 'softDeletesTz';
            }

            unset($columns['deleted_at']);
        }

        return $columns;
    }

    private function relativeClassName($model): string
    {
        $name = Blueprint::relativeNamespace(get_class($model));

        return ltrim(str_replace(config('blueprint.models_namespace'), '', $name), '\\');
    }
}
