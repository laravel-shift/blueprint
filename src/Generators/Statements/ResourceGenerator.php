<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Models\Model;
use Blueprint\Models\Statements\ResourceStatement;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ResourceGenerator implements Generator
{
    const INDENT = '            ';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Tree
     */
    private $tree;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $output = [];

        $stub = $this->filesystem->stub('resource.stub');

        /**
 * @var \Blueprint\Models\Controller $controller
*/
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (! $statement instanceof ResourceStatement) {
                        continue;
                    }

                    $path = $this->getPath(($controller->namespace() ? $controller->namespace() . '/' : '') . $statement->name());

                    if ($this->filesystem->exists($path)) {
                        continue;
                    }

                    if (! $this->filesystem->exists(dirname($path))) {
                        $this->filesystem->makeDirectory(dirname($path), 0755, true);
                    }

                    $this->filesystem->put($path, $this->populateStub($stub, $controller, $statement));

                    $output['created'][] = $path;
                }
            }
        }

        return $output;
    }

    public function types(): array
    {
        return ['controllers', 'resources'];
    }

    protected function getPath(string $name)
    {
        return Blueprint::appPath() . '/Http/Resources/' . $name . '.php';
    }

    protected function populateStub(string $stub, Controller $controller, ResourceStatement $resource)
    {
        $namespace = config('blueprint.namespace')
            . '\\Http\\Resources'
            . ($controller->namespace() ? '\\' . $controller->namespace() : '');

        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ import }}', $resource->collection() ? 'Illuminate\\Http\\Resources\\Json\\ResourceCollection' : 'Illuminate\\Http\\Resources\\Json\\JsonResource', $stub);
        $stub = str_replace('{{ parentClass }}', $resource->collection() ? 'ResourceCollection' : 'JsonResource', $stub);
        $stub = str_replace('{{ class }}', $resource->name(), $stub);
        $stub = str_replace('{{ parentClass }}', $resource->collection() ? 'ResourceCollection' : 'JsonResource', $stub);
        $stub = str_replace('{{ resource }}', $resource->collection() ? 'resource collection' : 'resource', $stub);
        $stub = str_replace('{{ body }}', $this->buildData($resource), $stub);

        if (Blueprint::supportsReturnTypeHits()) {
            $stub = str_replace('toArray($request)', 'toArray($request): array', $stub);
        }
        return $stub;
    }

    protected function buildData(ResourceStatement $resource)
    {
        $context = Str::singular($resource->reference());

        /**
 * @var \Blueprint\Models\Model $model
*/
        $model = $this->tree->modelForContext($context);

        $data = [];
        if ($resource->collection()) {
            $data[] = 'return [';
            $data[] = self::INDENT . '\'data\' => $this->collection,';
            $data[] = '        ];';

            return implode(PHP_EOL, $data);
        }

        $data[] = 'return [';
        foreach ($this->visibleColumns($model) as $column) {
            $data[] = self::INDENT . '\'' . $column . '\' => $this->' . $column . ',';
        }
        $data[] = '        ];';

        return implode(PHP_EOL, $data);
    }

    private function visibleColumns(Model $model)
    {
        return array_diff(
            array_keys($model->columns()),
            [
            'password',
            'remember_token',
            ]
        );
    }
}
