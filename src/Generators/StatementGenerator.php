<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;

abstract class StatementGenerator extends AbstractClassGenerator implements Generator
{
    protected function populateConstructor(string $type, $statement): string
    {
        $constructor = str_replace('{{ type }}', $type, $this->filesystem->stub('constructor.stub'));

        if (empty($statement->data())) {
            $stub = str_replace('{{ body }}', '//', $constructor);
        } else {
            $stub = $this->buildProperties($statement->data()) . PHP_EOL . PHP_EOL;
            $stub .= str_replace('__construct()', '__construct(' . $this->buildParameters($statement->data()) . ')', $constructor);
            $stub = str_replace('{{ body }}', $this->buildAssignments($statement->data()), $stub);
        }

        return trim($stub);
    }

    protected function buildProperties(array $data)
    {
        if (config('blueprint.property_promotion')) {
            return '';
        }

        return trim(
            array_reduce(
                $data,
                function ($output, $property) {
                    $output .= '    public $' . $property . ';' . PHP_EOL . PHP_EOL;

                    return $output;
                },
                ''
            )
        );
    }

    protected function buildAssignments(array $data)
    {
        if (config('blueprint.property_promotion')) {
            return '//';
        }

        return trim(
            array_reduce(
                $data,
                function ($output, $property) {
                    $output .= '        $this->' . $property . ' = $' . $property . ';' . PHP_EOL;

                    return $output;
                },
                ''
            )
        );
    }

    protected function buildParameters(array $data)
    {
        if (config('blueprint.property_promotion')) {
            $parameters = array_map(
                fn ($parameter) => 'public $' . $parameter,
                $data
            );
        } else {
            $parameters = array_map(
                fn ($parameter) => '$' . $parameter,
                $data
            );
        }

        return implode(', ', $parameters);
    }
}
