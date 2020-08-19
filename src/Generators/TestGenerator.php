<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Models\TestCase;
use Blueprint\Tree;
use Illuminate\Contracts\Filesystem\Filesystem;

class TestGenerator implements Generator
{
    /** @var Filesystem */
    private $files;

    /** @var Tree */
    private $tree;

    private $imports = [];
    private $stubs = [];
    private $traits = [];

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $stub = $this->files->stub('test.class.stub');

        $created = $tree->controllers()
            ->mapWithKeys(function (Controller $controller) use ($stub) {
                return [
                    $controller->getPath() => $this->populateStub($stub, $controller)
                ];
            })
            ->each(function ($populatedStub, $path) {
                $this->files->forcePut($path, $populatedStub);
            });

        return $created->isEmpty() ? [] : [
            'created' => $created->keys()->toArray(),
        ];
    }

    public function types(): array
    {
        return ['controllers', 'tests'];
    }

    public function populateStub(string $stub, Controller $controller)
    {
        $stub = str_replace('{{ namespace }}', 'Tests\\Feature\\' . Blueprint::relativeNamespace($controller->fullyQualifiedNamespace()), $stub);
        $stub = str_replace('{{ namespacedClass }}', '\\' . $controller->fullyQualifiedClassName(), $stub);
        $stub = str_replace('{{ class }}', $controller->className() . 'Test', $stub);
        $stub = str_replace('{{ body }}', $this->buildTestCases($controller), $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($controller), $stub);

        return $stub;
    }

    protected function buildTestCases(Controller $controller)
    {
        $test_cases = collect($controller->methods())
            ->map(function ($statements, $name) use ($controller) {
                $test = new TestCase($controller, $this->tree, $name, $statements);

                $test_case = $test->build($this->testCaseStub());
                $this->imports = array_merge_recursive($this->imports, $test->imports);
                $this->traits = array_merge_recursive($this->traits, $test->traits);

                return $test_case;
            });

        return trim($this->buildTraits($controller) . PHP_EOL . $test_cases->implode(''));
    }

    protected function addImport(Controller $controller, $class)
    {
        $this->imports[$controller->name()][] = $class;
    }

    protected function buildImports(Controller $controller)
    {
        $this->addImport($controller, 'Tests\\TestCase');

        $imports = array_unique($this->imports[$controller->name()]);
        sort($imports);

        return implode(PHP_EOL, array_map(function ($class) {
            return 'use ' . $class . ';';
        }, $imports));
    }

    private function buildTraits(Controller $controller)
    {
        if (empty($this->traits[$controller->name()])) {
            return '';
        }

        $traits = array_unique($this->traits[$controller->name()]);
        sort($traits);

        return 'use ' . implode(', ', $traits) . ';';
    }

    private function testCaseStub()
    {
        if (empty($this->stubs['test-case'])) {
            $this->stubs['test-case'] = $this->files->stub('test.case.stub');
        }

        return $this->stubs['test-case'];
    }
}
