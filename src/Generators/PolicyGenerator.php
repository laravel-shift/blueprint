<?php

namespace Blueprint\Generators;

use Blueprint\Concerns\HandlesImports;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Policy;
use Blueprint\Tree;
use Illuminate\Support\Str;

class PolicyGenerator extends AbstractClassGenerator implements Generator
{
    use HandlesImports;

    protected $types = ['policies'];

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $stub = $this->filesystem->stub('policy.class.stub');

        /** @var \Blueprint\Models\Policy $policy */
        foreach ($tree->policies() as $policy) {
            $this->addImport($policy, $policy->fullyQualifiedModelClassName());

            $path = $this->getPath($policy);

            $this->create($path, $this->populateStub($stub, $policy));
        }

        return $this->output;
    }

    protected function populateStub(string $stub, Policy $policy)
    {
        $stub = str_replace('{{ namespace }}', $policy->fullyQualifiedNamespace(), $stub);
        $stub = str_replace('{{ class }}', $policy->className(), $stub);
        $stub = str_replace('{{ methods }}', $this->buildMethods($policy), $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($policy), $stub);

        return $stub;
    }

    protected function buildMethods(Policy $policy)
    {
        $methods = '';

        foreach ($policy->methods() as $name) {
            $methods .= str_replace(
                [
                    '{{ modelClass }}',
                    '{{ modelVariable }}',
                ],
                [
                    Str::studly($policy->name()),
                    Str::camel($policy->name()),
                ],
                $this->filesystem->stub('policy.method.' . $name . '.stub'),
            ) . PHP_EOL;
        }

        return trim($methods);
    }
}
