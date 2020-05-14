<?php


namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Model;

class SeederGenerator implements Generator
{
    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        if (empty($tree['seeders'])) {
            return [];
        }

        $output = [];
        $stub = $this->files->stub('seeder.stub');

        foreach ($tree['seeders'] as $model) {
            $path = $this->getPath($model);
            $this->files->put($path, $this->populateStub($stub, $model));

            $output['created'][] = $path;
        }

        return $output;
    }

    private function getPath($model)
    {
        return 'database/seeds/' . $model . 'Seeder.php';
    }

    protected function populateStub(string $stub, string $model)
    {
        $stub = str_replace('DummyClass', $this->getClassName($model), $stub);
        $stub = str_replace('//', $this->build($model), $stub);

        return $stub;
    }

    private function getClassName(string $model)
    {
        return $model . 'Seeder';
    }

    private function build(string $model)
    {
        return sprintf('factory(\App\%s::class, 5)->create();', $model);
    }
}
