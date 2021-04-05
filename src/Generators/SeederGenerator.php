<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;

class SeederGenerator implements Generator
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Tree
     */
    private $tree;

    private $imports = [];

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        if (empty($tree->seeders())) {
            return [];
        }

        $output = [];

        if (Blueprint::isLaravel8OrHigher()) {
            $stub = $this->filesystem->stub('seeder.stub');
        } else {
            $stub = $this->filesystem->stub('seeder.no-factory.stub');
        }

        foreach ($tree->seeders() as $model) {
            $path = $this->getPath($model);
            $this->filesystem->put($path, $this->populateStub($stub, $model));

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
        if (Blueprint::isLaravel8OrHigher()) {
            $this->addImport($model, 'Illuminate\Database\Seeder');

            $stub = str_replace('//', $this->build($model), $stub);
            $stub = str_replace('use Illuminate\Database\Seeder;', $this->buildImports($model), $stub);
        } else {
            $stub = str_replace('{{ body }}', $this->build($model), $stub);
        }

        if (Blueprint::supportsReturnTypeHits()) {
            $stub = str_replace('public function run()', 'public function run(): void', $stub);
        }

        return $stub;
    }

    protected function getClassName(string $model)
    {
        return $model . 'Seeder';
    }

    protected function build(string $model)
    {
        if (Blueprint::isLaravel8OrHigher()) {
            $this->addImport($model, $this->tree->fqcnForContext($model));
            return sprintf('%s::factory()->count(5)->create();', class_basename($this->tree->fqcnForContext($model)));
        }
        return sprintf('factory(\\%s::class, 5)->create();', $this->tree->fqcnForContext($model));
    }

    protected function buildImports(string $model)
    {
        $imports = array_unique($this->imports[$model]);
        sort($imports);

        return implode(
            PHP_EOL,
            array_map(
                function ($class) {
                    return 'use ' . $class . ';';
                },
                $imports
            )
        );
    }

    private function addImport(string $model, $class)
    {
        $this->imports[$model][] = $class;
    }

    private function getPath($model)
    {
        if (Blueprint::isLaravel8OrHigher()) {
            return 'database/seeders/' . $model . 'Seeder.php';
        }

        return 'database/seeds/' . $model . 'Seeder.php';
    }
}
