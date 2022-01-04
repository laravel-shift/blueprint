<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;

class ViewGenerator implements Generator
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function output(Tree $tree): array
    {
        $output = [];

        $stub = $this->filesystem->stub('view.stub');

        /**
 * @var \Blueprint\Models\Controller $controller
*/
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (! $statement instanceof RenderStatement) {
                        continue;
                    }

                    $path = $this->getPath($statement->view());

                    if ($this->filesystem->exists($path)) {
                        $output['skipped'][] = $path;
                        continue;
                    }

                    if (! $this->filesystem->exists(dirname($path))) {
                        $this->filesystem->makeDirectory(dirname($path), 0755, true);
                    }

                    $this->filesystem->put($path, $this->populateStub($stub, $statement));

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
        return 'resources/views/' . str_replace('.', '/', $view) . '.blade.php';
    }

    protected function populateStub(string $stub, RenderStatement $renderStatement)
    {
        return str_replace('{{ view }}', $renderStatement->view(), $stub);
    }
}
