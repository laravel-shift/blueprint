<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\DispatchStatement;
use Blueprint\Tree;

class JobGenerator extends StatementGenerator
{
    protected $new_instance = 'new job instance';

    public function output(Tree $tree): array
    {
        $output = [];

        $stub = $this->files->stub('job.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (! $statement instanceof DispatchStatement) {
                        continue;
                    }

                    $path = $this->getPath($statement->job());

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

    public function types(): array
    {
        return ['controllers'];
    }

    protected function getPath(string $name)
    {
        return Blueprint::appPath().'/Jobs/'.$name.'.php';
    }

    protected function populateStub(string $stub, DispatchStatement $dispatchStatement)
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace').'\\Jobs', $stub);
        $stub = str_replace('{{ class }}', $dispatchStatement->job(), $stub);
        $stub = str_replace('{{ properties }}', $this->buildConstructor($dispatchStatement), $stub);

        return $stub;
    }
}
