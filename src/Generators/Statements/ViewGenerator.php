<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Tree;

class ViewGenerator extends StatementGenerator implements Generator
{
    protected $types = ['controllers', 'views'];

    public function output(Tree $tree): array
    {
        $stub = $this->filesystem->stub('view.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof RenderStatement) {
                        continue;
                    }

                    $path = $this->getStatementPath($statement->view());

                    if ($this->filesystem->exists($path)) {
                        $this->output['skipped'][] = $path;
                        continue;
                    }

                    $this->create($path, $this->populateStub($stub, $statement));
                }
            }
        }

        return $this->output;
    }

    protected function getStatementPath(string $view)
    {
        return 'resources/views/' . str_replace('.', '/', $view) . '.blade.php';
    }

    protected function populateStub(string $stub, RenderStatement $renderStatement)
    {
        return str_replace('{{ view }}', $renderStatement->view(), $stub);
    }
}
