<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class TraceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:trace';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create definitions for existing models to reference in new drafts';

    /** @var Filesystem $files */
    protected $files;

    /**
     * @param Filesystem $files
     * @param \Illuminate\Contracts\View\Factory $view
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $definitions = [];
        foreach ($this->appClasses() as $class) {
            $model = $this->loadModel($class);
            if (is_null($model)) {
                continue;
            }

            $definitions[$this->relativeClassName($model)] = $this->translateColumns($this->mapColumns($this->extractColumns($model)));
        }

        if (empty($definitions)) {
            $this->error('No models found');

            return;
        }

        $blueprint = new Blueprint();

        $cache = [];
        if ($this->files->exists('.blueprint')) {
            $cache = $blueprint->parse($this->files->get('.blueprint'));
        }

        $cache['models'] = $definitions;

        $this->files->put('.blueprint', $blueprint->dump($cache));

        $this->info('Traced ' . count($definitions) . ' ' . Str::plural('model', count($definitions)));
    }

    private function appClasses()
    {
        $dir = config('blueprint.app_path');

        if (config('blueprint.models_namespace')) {
            $dir .= DIRECTORY_SEPARATOR . str_replace('\\', '/', config('blueprint.models_namespace'));
        }

        if (!$this->files->exists($dir)) {
            return [];
        }

        return array_map(function (\SplFIleInfo $file) {
            return str_replace(
                [config('blueprint.app_path') . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
                [config('blueprint.namespace') . '\\', '\\'],
                $file->getPath() . DIRECTORY_SEPARATOR . $file->getBasename('.php')
            );
        }, $this->files->allFiles($dir));
    }

    private function loadModel(string $class)
    {
        if (!class_exists($class)) {
            return null;
        }

        $reflectionClass = new \ReflectionClass($class);
        if (!$reflectionClass->isSubclassOf(\Illuminate\Database\Eloquent\Model::class)) {
            return null;
        }

        return $this->laravel->make($class);
    }

    private function extractColumns(Model $model)
    {
        $table = $model->getConnection()->getTablePrefix() . $model->getTable();
        $schema = $model->getConnection()->getDoctrineSchemaManager();

        $database = null;
        if (strpos($table, '.')) {
            list($database, $table) = explode('.', $table);
        }

        $columns = $schema->listTableColumns($table, $database);

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
        }

        // TODO: enums, guid/uuid

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
        if (config('blueprint.models_namespace')) {
            return $name;
        }

        return ltrim(str_replace(config('blueprint.models_namespace'), '', $name), '\\');
    }
}
