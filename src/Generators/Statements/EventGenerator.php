<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\FireStatement;
use Blueprint\Tree;

class EventGenerator extends StatementGenerator
{
    protected $new_instance = 'new event instance';

    public function output(Tree $tree): array
    {
        $output = [];

        $stub = $this->files->stub('event.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (! $statement instanceof FireStatement) {
                        continue;
                    }

                    if ($statement->isNamedEvent()) {
                        continue;
                    }

                    $path = $this->getPath($statement->event());

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
        return Blueprint::appPath().'/Events/'.$name.'.php';
    }

    protected function populateStub(string $stub, FireStatement $fireStatement)
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace').'\\Events', $stub);
        $stub = str_replace('{{ class }}', $fireStatement->event(), $stub);
        $stub = str_replace('{{ properties }}', $this->buildConstructor($fireStatement), $stub);

        return $stub;
    }
}
