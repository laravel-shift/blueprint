<?php

namespace Blueprint\Concerns;

use Blueprint\Contracts\Model;

trait HandlesImports
{
    protected $imports = [];

    protected function addImport(Model $model, $class)
    {
        $this->imports[$model->name()][] = $class;
    }

    protected function buildImports(Model $model)
    {
        return collect($this->imports[$model->name()] ?? [])
            ->map(fn ($class) => "use {$class};")
            ->unique()
            ->sort()
            ->implode(PHP_EOL);
    }
}
