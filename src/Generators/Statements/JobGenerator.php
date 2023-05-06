<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\DispatchStatement;
use Blueprint\Tree;

class JobGenerator extends StatementGenerator
{
    protected $types = ['controllers'];

    public function output(Tree $tree): array
    {
        $stub = $this->filesystem->stub('job.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof DispatchStatement) {
                        continue;
                    }

                    $path = $this->getStatementPath($statement->job());

                    if ($this->filesystem->exists($path)) {
                        continue;
                    }

                    $this->create($path, $this->populateStub($stub, $statement));
                }
            }
        }

        return $this->output;
    }

    protected function getStatementPath(string $name)
    {
        return Blueprint::appPath() . '/Jobs/' . $name . '.php';
    }

    protected function populateStub(string $stub, DispatchStatement $dispatchStatement)
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace') . '\\Jobs', $stub);
        $stub = str_replace('{{ class }}', $dispatchStatement->job(), $stub);
        $stub = str_replace('{{ properties }}', $this->populateConstructor('job', $dispatchStatement), $stub);

        return $stub;
    }
}
