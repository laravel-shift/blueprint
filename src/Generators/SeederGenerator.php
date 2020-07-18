<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Model;
use Illuminate\Support\Str;

class SeederGenerator implements Generator
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    private $models = [];

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        if (empty($tree['seeders'])) {
            return [];
        }

        $output = [];

        $stub = $this->files->stub('seeder.stub');

        $this->registerModels($tree);

        foreach ($tree['seeders'] as $model) {
            $path = $this->getPath($model);
            $this->files->put($path, $this->populateStub($stub, $model));

            $output['created'][] = $path;
        }

        return $output;
    }

    public function types(): array
    {
        return ['seeders'];
    }

    protected function populateStub(string $stub, string $model)
    {
        $stub = str_replace('DummyClass', $this->getClassName($model), $stub);
        $stub = str_replace('//', $this->build($model), $stub);

        return $stub;
    }

    protected function getClassName(string $model)
    {
        return $model.'Seeder';
    }

    protected function build(string $model)
    {
        return sprintf('factory(\\%s::class, 5)->create();', $this->fqcnForContext($model));
    }

    private function getPath($model)
    {
        return 'database/seeds/' . $model . 'Seeder.php';
    }

    private function registerModels(array $tree)
    {
        $this->models = array_merge($tree['cache'] ?? [], $tree['models'] ?? []);
    }

    private function fqcnForContext(string $context)
    {
        if (isset($this->models[$context])) {
            return $this->models[$context]->fullyQualifiedClassName();
        }

        $matches = array_filter(array_keys($this->models), function ($key) use ($context) {
            return Str::endsWith($key, '\\'.Str::studly($context));
        });

        if (count($matches) === 1) {
            return $this->models[current($matches)]->fullyQualifiedClassName();
        }

        $fqn = config('blueprint.namespace');
        if (config('blueprint.models_namespace')) {
            $fqn .= '\\'.config('blueprint.models_namespace');
        }

        return $fqn.'\\'.$context;
    }
}
