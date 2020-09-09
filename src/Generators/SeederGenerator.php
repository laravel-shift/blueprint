<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Tree;
use Illuminate\Support\Facades\App;

class SeederGenerator implements Generator
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    /** @var Tree */
    private $tree;

    private $imports = [];

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        if (empty($tree->seeders())) {
            return [];
        }

        $output = [];

        if ($this->isLaravel8Up()) {
            $stub = $this->files->stub('seeder.stub');
        } else {
            $stub = $this->files->stub('seeder.no-factory.stub');
        }

        foreach ($tree->seeders() as $model) {
            $path = $this->getPath($model);
            $this->files->put($path, $this->populateStub($stub, $model));

            $output['created'][] = $path;
        }

        return $output;
    }

    public function types(): array
    {
        return ['seeders'];
    }

    protected function populateStub(string $stub, string $model)
    {
        $stub = str_replace('{{ class }}', $this->getClassName($model), $stub);
        if ($this->isLaravel8Up()) {
            $this->addImport($model, 'Illuminate\Database\Seeder');

            $stub = str_replace('//', $this->build($model), $stub);
            $stub = str_replace('use Illuminate\Database\Seeder;', $this->buildImports($model), $stub);
        } else {
            $stub = str_replace('{{ body }}', $this->build($model), $stub);
        }
        return $stub;
    }

    protected function getClassName(string $model)
    {
        return $model.'Seeder';
    }

    protected function build(string $model)
    {
        if ($this->isLaravel8Up()) {
            $this->addImport($model, $this->tree->fqcnForContext($model));
            return sprintf('%s::factory()->times(5)->create();', class_basename($this->tree->fqcnForContext($model)));
        }
        return sprintf('factory(\\%s::class, 5)->create();', $this->tree->fqcnForContext($model));
    }

    protected function buildImports(string $model)
    {
        $imports = array_unique($this->imports[$model]);
        sort($imports);

        return implode(PHP_EOL, array_map(function ($class) {
            return 'use '.$class.';';
        }, $imports));
    }

    private function addImport(string $model, $class)
    {
        $this->imports[$model][] = $class;
    }

    private function getPath($model)
    {
        return 'database/seeds/'.$model.'Seeder.php';
    }

    protected function isLaravel8Up()
    {
        return version_compare(App::version(), '8.0.0', '>=');
    }
}
