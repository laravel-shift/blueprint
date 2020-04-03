<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Models\Statements\ResourceStatement;

class ResourceGenerator implements Generator
{
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

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof ResourceStatement) {
                        continue;
                    }

                    $path = $this->getPath($statement->name());

                    if ($this->files->exists($path)) {
                        continue;
                    }

                    if (!$this->files->exists(dirname($path))) {
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
        return config('blueprint.app_path') . '/Http/Resources/' . $name . '.php';
    }

    protected function populateStub(string $stub, ResourceStatement $resource)
    {
        $stub = str_replace('DummyNamespace', config('blueprint.namespace') . '\\Http\\Resources', $stub);
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
        if ($resource->collection()) {
            return 'return [
            \'data\' => $this->collection,
        ];';
        }

        return 'return [
            \'id\' => $this->id,
        ];';
    }
}
