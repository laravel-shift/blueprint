<?php

namespace Blueprint\Generators;

use Blueprint\Column;
use Blueprint\Contracts\Generator;
use Blueprint\Controller;
use Illuminate\Support\Str;

class ControllerGenerator implements Generator
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->get(STUBS_PATH . '/controller/class.stub');

        /** @var \Blueprint\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            $path = $this->getPath($controller);
            $this->files->put(
                $path,
                $this->populateStub($stub, $controller)
            );

            $output['created'][] = $path;
        }

        return $output;
    }

    protected function populateStub(string $stub, Controller $controller)
    {
        $stub = str_replace('DummyNamespace', 'App\\Http\\Controllers', $stub);
        $stub = str_replace('DummyClass', $this->className($controller), $stub);
        $stub = str_replace('// methods...', $this->buildMethods($controller), $stub);
        $stub = $this->addImports($controller, $stub);

        return $stub;
    }

    private function buildMethods(Controller $controller)
    {
        $template = $this->methodStub();

        $methods = '';

        foreach ($controller->methods() as $name => $body) {
            $methods .= PHP_EOL . str_replace('DummyMethod', $name, $template);
            // TODO:
            // foreach statements
            // output their resulting code
            // validate => replace Request injection (addImport)
            // queue => output Job::dispatch($data) (addImport)
        }

        return trim($methods);
    }

    protected function getPath(Controller $controller)
    {
        return 'app/Http/Controllers/' . $this->className($controller) . '.php';
    }

    private function methodStub()
    {
        static $stub = '';

        if (empty($stub)) {
            $stub = $this->files->get(STUBS_PATH . '/controller/method.stub');
        }

        return $stub;
    }

    private function addImports(Controller $controller, $stub)
    {
        return $stub;
    }

    protected function className(Controller $controller): string
    {
        return $controller->name() . (Str::endsWith($controller->name(), 'Controller') ? '' : 'Controller');
    }
}
