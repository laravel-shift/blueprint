<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Controller;
use Blueprint\Models\Model;
use Blueprint\Models\Statements\ResourceStatement;
use Blueprint\Tree;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ResourceGenerator extends StatementGenerator implements Generator
{
    const INDENT = '            ';

    protected $types = ['controllers', 'resources'];

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $stub = $this->filesystem->stub('resource.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof ResourceStatement) {
                        continue;
                    }

                    $path = $this->getStatementPath(($controller->namespace() ? $controller->namespace() . '/' : '') . $statement->name());

                    if ($this->filesystem->exists($path)) {
                        continue;
                    }

                    $this->create($path, $this->populateStub($stub, $controller, $statement));
                }
            }
        }

        return $this->output;
    }

    protected function getStatementPath(string $name)
    {
        return Blueprint::appPath() . '/Http/Resources/' . $name . '.php';
    }

    protected function populateStub(string $stub, Controller $controller, ResourceStatement $resource)
    {
        $namespace = config('blueprint.namespace')
            . '\\Http\\Resources'
            . ($controller->namespace() ? '\\' . $controller->namespace() : '');

        $imports = ['use Illuminate\\Http\\Request;'];
        $imports[] = $resource->collection() ? 'use Illuminate\\Http\\Resources\\Json\\ResourceCollection;' : 'use Illuminate\\Http\\Resources\\Json\\JsonResource;';

        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ imports }}', implode(PHP_EOL, $imports), $stub);
        $stub = str_replace('{{ parentClass }}', $resource->collection() ? 'ResourceCollection' : 'JsonResource', $stub);
        $stub = str_replace('{{ class }}', $resource->name(), $stub);
        $stub = str_replace('{{ parentClass }}', $resource->collection() ? 'ResourceCollection' : 'JsonResource', $stub);
        $stub = str_replace('{{ resource }}', $resource->collection() ? 'resource collection' : 'resource', $stub);
        $stub = str_replace('{{ body }}', $this->buildData($resource), $stub);

        return $stub;
    }

    protected function buildData(ResourceStatement $resource)
    {
        $context = Str::singular($resource->reference());

        /**
         * @var \Blueprint\Models\Model $model
         */
        $model = $this->tree->modelForContext($context, true);

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

        foreach ($model->relationships() as $type => $relationship) {
            $method_name = lcfirst(Str::afterLast(Arr::last($relationship), '\\'));

            $relation_model = $this->tree->modelForContext($method_name);

            if ($relation_model === null) {
                continue;
            }

            if (in_array($type, ['hasMany', 'belongsToMany', 'morphMany'])) {
                $relation_resource_name = $relation_model->name() . 'Collection';
                $method_name = Str::plural($method_name);
            } else {
                $relation_resource_name = $relation_model->name() . 'Resource';
            }

            $data[] = self::INDENT . '\'' . $method_name . '\' => ' . $relation_resource_name . '::make($this->whenLoaded(\'' . $method_name . '\')),';
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
