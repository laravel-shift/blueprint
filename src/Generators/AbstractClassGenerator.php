<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Concerns\HandlesImports;
use Blueprint\Concerns\HandlesTraits;
use Blueprint\Contracts\Model;
use Illuminate\Filesystem\Filesystem;

class AbstractClassGenerator
{
    use HandlesImports, HandlesTraits;

    const INDENT = '        ';

    protected $filesystem;

    private $tree;

    protected $imports = [];
    protected $traits = [];
    protected $stubs = [];
    protected $output = [];
    protected $pathSignature = '%s/%s.php';

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
        return sprintf($this->pathSignature, $this->basePath ?? Blueprint::appPath(), $path);
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
