<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Statements\FireStatement;

class EventGenerator implements Generator
{
    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->stub('event.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof FireStatement) {
                        continue;
                    }

                    if ($statement->isNamedEvent()) {
                        continue;
                    }

                    $path = $this->getPath($statement->event());

                    if ($this->files->exists($path)) {
                        continue;
                    }

                    if (!$this->files->exists(dirname($path))) {
                        $this->files->makeDirectory(dirname($path));
                    }

                    $this->files->put(
                        $path,
                        $this->populateStub($stub, $statement)
                    );

                    $output['created'][] = $path;
                }
            }
        }
        return $output;
    }

    protected function getPath(string $name)
    {
        return config('blueprint.app_path') . '/Events/' . $name . '.php';
    }

    protected function populateStub(string $stub, FireStatement $fireStatement)
    {
        $stub = str_replace('DummyNamespace', config('blueprint.namespace') . '\\Events', $stub);
        $stub = str_replace('DummyClass', $fireStatement->event(), $stub);
        $stub = str_replace('// properties...', $this->buildConstructor($fireStatement), $stub);

        return $stub;
    }

    private function buildConstructor(FireStatement $fireStatement)
    {
        static $constructor = null;

        if (is_null($constructor)) {
            $constructor = str_replace('new instance', 'new event instance', $this->files->stub('partials/constructor.stub'));
        }

        if (empty($fireStatement->data())) {
            return trim($constructor);
        }

        $stub = $this->buildProperties($fireStatement->data()) . PHP_EOL . PHP_EOL;
        $stub .= str_replace('__construct()', '__construct(' . $this->buildParameters($fireStatement->data()) . ')', $constructor);
        $stub = str_replace('//', $this->buildAssignments($fireStatement->data()), $stub);

        return $stub;
    }

    private function buildProperties(array $data)
    {
        return trim(array_reduce($data, function ($output, $property) {
            $output .= '    public $' . $property . ';' . PHP_EOL . PHP_EOL;
            return $output;
        }, ''));
    }

    private function buildParameters(array $data)
    {
        $parameters = array_map(function ($parameter) {
            return '$' . $parameter;
        }, $data);

        return implode(', ', $parameters);
    }

    private function buildAssignments(array $data)
    {
        return trim(array_reduce($data, function ($output, $property) {
            $output .= '        $this->' . $property . ' = $' . $property . ';' . PHP_EOL;
            return $output;
        }, ''));
    }
}
