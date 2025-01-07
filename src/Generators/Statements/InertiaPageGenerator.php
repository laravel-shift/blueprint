<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Models\Controller;
use Blueprint\Contracts\Generator;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\InertiaStatement;
use Blueprint\Tree;

class InertiaPageGenerator extends StatementGenerator implements Generator
{
    protected array $types = ['controllers', 'views'];

    public function output(Tree $tree): array
    {
        $stub = $this->filesystem->stub('inertia.vue.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof InertiaStatement) {
                        continue;
                    }

                    $path = $this->getStatementPath($statement->view());

                    if ($this->filesystem->exists($path)) {
                        $this->output['skipped'][] = $path;
                        continue;
                    }

                    $this->create($path, $this->populateStub($stub, $statement, $controller));
                }
            }
        }

        return $this->output;
    }

    protected function getStatementPath(string $view): string
    {
        return 'resources/js/Pages/' . str_replace('.', '/', $view) . '.vue';
    }

    protected function populateStub(string $stub, InertiaStatement $inertiaStatement, Controller $controller): string
    {
        return str_replace(['{{ props }}', '{{ view }}'], [json_encode($inertiaStatement->data()), $controller->name()], $stub);
    }
}
