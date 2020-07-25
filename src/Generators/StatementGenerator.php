<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;

abstract class StatementGenerator implements Generator
{
    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var string
     */
    protected $new_instance = 'new instance';

    public function __construct($files)
    {
        $this->files = $files;
    }

    protected function buildConstructor($statement)
    {
        static $constructor = null;

        if (is_null($constructor)) {
            $constructor = str_replace('new instance', $this->new_instance, $this->files->stub('constructor.stub'));
        }

        if (empty($statement->data())) {
            $stub = (str_replace('{{ body }}', '//', $constructor));
        } else {
            $stub = $this->buildProperties($statement->data()) . PHP_EOL . PHP_EOL;
            $stub .= str_replace('__construct()', '__construct(' . $this->buildParameters($statement->data()) . ')', $constructor);
            $stub = str_replace('{{ body }}', $this->buildAssignments($statement->data()), $stub);
        }

        return trim($stub);
    }

    protected function buildProperties(array $data)
    {
        return trim(array_reduce($data, function ($output, $property) {
            $output .= '    public $' . $property . ';' . PHP_EOL . PHP_EOL;

            return $output;
        }, ''));
    }

    protected function buildAssignments(array $data)
    {
        return trim(array_reduce($data, function ($output, $property) {
            $output .= '        $this->' . $property . ' = $' . $property . ';' . PHP_EOL;

            return $output;
        }, ''));
    }

    protected function buildParameters(array $data)
    {
        $parameters = array_map(function ($parameter) {
            return '$' . $parameter;
        }, $data);

        return implode(', ', $parameters);
    }
}
