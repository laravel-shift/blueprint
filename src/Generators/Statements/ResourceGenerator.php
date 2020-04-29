<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Models\Model;
use Blueprint\Models\Statements\ResourceStatement;
use Illuminate\Support\Str;

class ResourceGenerator implements Generator
{
    const INDENT = '            ';
    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $files;

    private $models = [];

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->stub('resource.stub');

        $this->registerModels($tree);

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (! $statement instanceof ResourceStatement) {
                        continue;
                    }

                    $path = $this->getPath($statement->name());

                    if ($this->files->exists($path)) {
                        continue;
                    }

                    if (! $this->files->exists(dirname($path))) {
                        $this->files->makeDirectory(dirname($path), 0755, true);
                    }

                    $this->files->put($path, $this->populateStub($stub, $statement));

                    $output['created'][] = $path;
                }
            }
        }

        return $output;
    }

    protected function getPath(string $name)
    {
        return Blueprint::appPath().'/Http/Resources/'.$name.'.php';
    }

    protected function populateStub(string $stub, ResourceStatement $resource)
    {
        $stub = str_replace('DummyNamespace', config('blueprint.namespace').'\\Http\\Resources', $stub);
        $stub = str_replace('DummyImport', $resource->collection() ? 'Illuminate\\Http\\Resources\\Json\\ResourceCollection' : 'Illuminate\\Http\\Resources\\Json\\JsonResource', $stub);
        $stub = str_replace('DummyParent', $resource->collection() ? 'ResourceCollection' : 'JsonResource', $stub);
        $stub = str_replace('DummyClass', $resource->name(), $stub);
        $stub = str_replace('DummyParent', $resource->collection() ? 'ResourceCollection' : 'JsonResource', $stub);
        $stub = str_replace('DummyItem', $resource->collection() ? 'resource collection' : 'resource', $stub);
        $stub = str_replace('// data...', $this->buildData($resource), $stub);

        return $stub;
    }

    private function buildData(ResourceStatement $resource)
    {
        $context = Str::singular($resource->reference());

        /** @var \Blueprint\Models\Model $model */
        $model = $this->modelForContext($context);

        $data = [];
        if ($resource->collection()) {
            $data[] = 'return [';
            $data[] = self::INDENT.'\'data\' => $this->collection,';
            $data[] = '        ];';

            return implode(PHP_EOL, $data);
        }

        $data[] = 'return [';
        foreach ($this->visibleColumns($model) as $column) {
            $data[] = self::INDENT.'\''.$column.'\' => $this->'.$column.',';
        }
        $data[] = '        ];';

        return implode(PHP_EOL, $data);
    }

    private function visibleColumns(Model $model)
    {
        return array_diff(array_keys($model->columns()), [
            'password',
            'remember_token',
        ]);
    }

    private function modelForContext(string $context)
    {
        if (isset($this->models[Str::studly($context)])) {
            return $this->models[Str::studly($context)];
        }

        $matches = array_filter(array_keys($this->models), function ($key) use ($context) {
            return Str::endsWith($key, '/'.Str::studly($context));
        });

        if (count($matches) === 1) {
            return $this->models[$matches[0]];
        }
    }

    private function registerModels(array $tree)
    {
        $this->models = array_merge($tree['cache'] ?? [], $tree['models'] ?? []);
    }
}
