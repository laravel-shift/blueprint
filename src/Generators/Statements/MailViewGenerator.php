<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Tree;

class MailViewGenerator extends StatementGenerator
{
    protected $new_instance = 'new message view instance';

    public function output(Tree $tree): array
    {
        $output = [];

        $stub = $this->filesystem->stub('mailView.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
        */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (! $statement instanceof SendStatement) {
                        continue;
                    }

                    if ($statement->type() !== SendStatement::TYPE_MAIL) {
                        continue;
                    }

                    if (! $statement->view()) {
                        continue;
                    }

                    $path = $this->getPath($statement->view());

                    if ($this->filesystem->exists($path)) {
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
        return ['controllers'];
    }

    protected function getPath(string $view)
    {
        return 'resources/views/' . str_replace('.', '/', 'email.'.$view) . '.blade.php';
    }

    protected function populateStub(string $stub, SendStatement $sendStatement)
    {
         $stub = str_replace('{{ view }}', $sendStatement->view(), $stub);

        if (Blueprint::useReturnTypeHints()) {
            $stub = str_replace('{{ typehint }}', $this->buildTypehint($sendStatement->data()), $stub);
        }else{
            $stub = str_replace('{{ typehint }}', '', $stub);   
        }
        $stub = trim($stub);

        return $stub;
    }

}
