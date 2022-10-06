<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Model;
use Illuminate\Filesystem\Filesystem;

class AbstractClassGenerator
{
    public const INDENT = '        ';

    protected $filesystem;

    protected $tree;

    protected $output = [];

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function types(): array
    {
        return $this->types;
    }

    protected function getPath(Model $model)
    {
        $path = str_replace('\\', '/', Blueprint::relativeNamespace($model->fullyQualifiedClassName()));

        return sprintf('%s/%s.php', $this->basePath ?? Blueprint::appPath(), $path);
    }

    protected function create(string $path, $content)
    {
        if (!$this->filesystem->exists(dirname($path))) {
            $this->filesystem->makeDirectory(dirname($path), 0755, true);
        }

        $this->filesystem->put($path, $content);

        $this->output['created'][] = $path;
    }
}
