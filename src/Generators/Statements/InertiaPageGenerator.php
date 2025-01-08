<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Contracts\Generator;
use Blueprint\Generators\StatementGenerator;
use Blueprint\Models\Statements\InertiaStatement;
use Blueprint\Tree;
use Illuminate\Support\Str;

class InertiaPageGenerator extends StatementGenerator implements Generator
{
    protected array $types = [];

    protected array $adapters = [
        'vue3' => ['framework' => 'vue', 'extension' => '.vue'],
        'react' => ['framework' => 'react', 'extension' => '.jsx'],
        'svelte' => ['framework' => 'svelte', 'extension' => '.svelte'],
    ];

    protected ?array $adapter = null;

    public function output(Tree $tree): array
    {
        $this->adapter = $this->getAdapter();

        if (!$this->adapter) {
            return $this->output;
        }

        $stub = $this->filesystem->stub('inertia.' . $this->adapter['framework'] . '.stub');

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

                    $this->create($path, $this->populateStub($stub, $statement));
                }
            }
        }

        return $this->output;
    }

    protected function getAdapter(): ?array
    {
        $packagePath = base_path('package.json');

        if (!$this->filesystem->exists($packagePath)) {
            return null;
        }

        $contents = $this->filesystem->get($packagePath);

        if (preg_match('/@inertiajs\/(vue3|react|svelte)/i', $contents, $matches)) {
            $adapterKey = strtolower($matches[1]);

            return $this->adapters[$adapterKey] ?? null;
        }

        return null;
    }

    protected function getStatementPath(string $view): string
    {
        return 'resources/js/Pages/' . str_replace('.', '/', $view) . $this->adapter['extension'];
    }

    protected function populateStub(string $stub, InertiaStatement $inertiaStatement): string
    {
        $data = $inertiaStatement->data();
        $props = $this->adapter['framework'] === 'vue' ? json_encode($data) : '{ ' . implode(', ', $data) . ' }';
        $componentName = $this->adapter['framework'] === 'react' ? Str::afterLast($inertiaStatement->view(), '/') : null;

        return str_replace([
            '{{ componentName }}',
            '{{ props }}',
            '{{ view }}',
        ], [
            $componentName,
            $props,
            str_replace('/', ' ', $inertiaStatement->view()),
        ], $stub);
    }
}
