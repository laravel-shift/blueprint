<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Tree;

class MailGenerator extends StatementGenerator
{
    protected $new_instance = 'new message instance';

    public function output(Tree $tree): array
    {
        $output = [];

        $stub = $this->files->stub('mail.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (! $statement instanceof SendStatement) {
                        continue;
                    }

                    if ($statement->type() !== SendStatement::TYPE_MAIL) {
                        continue;
                    }

                    $path = $this->getPath($statement->mail());

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
        return Blueprint::appPath().'/Mail/'.$name.'.php';
    }

    protected function populateStub(string $stub, SendStatement $sendStatement)
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace').'\\Mail', $stub);
        $stub = str_replace('{{ class }}', $sendStatement->mail(), $stub);
        $stub = str_replace('{{ properties }}', $this->buildConstructor($sendStatement), $stub);

        return $stub;
    }
}
