<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Tree;

class ViewGenerator implements Generator
{
    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(Tree $tree): array
    {
        $output = [];

        $stub = $this->files->stub('view.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (! $statement instanceof RenderStatement) {
                        continue;
                    }

                    $path = $this->getPath($statement->view());

                    if ($this->files->exists($path)) {
                        // TODO: mark skipped...
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
        return ['controllers', 'views'];
    }

    protected function getPath(string $view)
    {
        return 'resources/views/'.str_replace('.', '/', $view).'.blade.php';
    }

    protected function populateStub(string $stub, RenderStatement $renderStatement)
    {
        return str_replace('{{ view }}', $renderStatement->view(), $stub);
    }
}
