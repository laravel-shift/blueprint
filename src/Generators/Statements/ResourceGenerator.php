<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Illuminate\Support\Str;

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

        $this->registerModels($tree['models'] ?? []);

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            if (!$controller->isAPI()) {
                continue;
            }
            $context = Str::singular($controller->prefix());
            $name = $this->getName($context);
            $path = $this->getPath($controller, $name);

            if ($this->files->exists($path)) {
                continue;
            }

            if (!$this->files->exists(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0755, true);
            }

            $this->files->put(
                $path,
                $this->populateStub($stub, $name, $controller)
            );

            $output['created'][] = $path;
        }

        return $output;
    }

    protected function getPath(Controller $controller, string $name)
    {
        return config('blueprint.app_path') .
            '/Http/Resources/' .
            ($controller->namespace() ? $controller->namespace() . '/' : '') .
            $name .
            '.php';
    }

    protected function populateStub(
        string $stub,
        string $name,
        Controller $controller
    ) {
        $stub = str_replace(
            'DummyNamespace',
            config('blueprint.namespace') .
                '\\Http\\Resources' .
                ($controller->namespace()
                    ? '\\' . $controller->namespace()
                    : ''),
            $stub
        );
        $stub = str_replace('DummyClass', $name, $stub);

        return $stub;
    }

    private function getName(string $context)
    {
        return $context . 'Resource';
    }

    private function registerModels(array $models)
    {
        $this->models = $models;
    }
}
