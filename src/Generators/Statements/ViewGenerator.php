<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Statements\RenderStatement;

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

    public function output(array $tree, array $only = [], array $skip = []): array
    {
        $output = [];

        if ($this->shouldGenerate($only, $skip)) {
            $stub = $this->files->stub('view.stub');

            /** @var \Blueprint\Models\Controller $controller */
            foreach ($tree['controllers'] as $controller) {
                foreach ($controller->methods() as $method => $statements) {
                    foreach ($statements as $statement) {
                        if (!$statement instanceof RenderStatement) {
                            continue;
                        }

                        $path = $this->getPath($statement->view());

                        if ($this->files->exists($path)) {
                            // TODO: mark skipped...
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
        }

        return $output;
    }

    protected function shouldGenerate(array $only, array $skip): bool
    {
        if (count($only)) {
            return in_array('views', $only);
        }

        if (count($skip)) {
            return !in_array('views', $skip);
        }

        return true;
    }

    protected function getPath(string $view)
    {
        return 'resources/views/' . str_replace('.', '/', $view) . '.blade.php';
    }

    protected function populateStub(string $stub, RenderStatement $renderStatement)
    {
        return str_replace('DummyView', $renderStatement->view(), $stub);
    }
}
