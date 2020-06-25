<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Statements\SendStatement;

class NotificationGenerator implements Generator
{
    /**
     * @
 \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->stub('notification.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (! $statement instanceof SendStatement) {
                        continue;
                    }

                    if (! $statement->isNotification()) {
                        continue;
                    }

                    $path = $this->getPath($statement->mail());

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
        return Blueprint::appPath() . '/Notification/' . $name . '.php';
    }

    protected function populateStub(string $stub, SendStatement $sendStatement)
    {
        $stub = str_replace('DummyNamespace', config('blueprint.namespace') . '\\Notification', $stub);
        $stub = str_replace('DummyClass', $sendStatement->mail(), $stub);
        $stub = str_replace('// properties...', $this->buildConstructor($sendStatement), $stub);

        return $stub;
    }

    private function buildConstructor(SendStatement $sendStatement)
    {
        static $constructor = null;

        if (is_null($constructor)) {
            $constructor = str_replace('new instance', 'new message instance', $this->files->stub('partials/constructor.stub'));
        }

        if (empty($sendStatement->data())) {
            return trim($constructor);
        }

        $stub = $this->buildProperties($sendStatement->data()) . PHP_EOL . PHP_EOL;
        $stub .= str_replace('__construct()', '__construct(' . $this->buildParameters($sendStatement->data()) . ')', $constructor);
        $stub = str_replace('//', $this->buildAssignments($sendStatement->data()), $stub);

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
