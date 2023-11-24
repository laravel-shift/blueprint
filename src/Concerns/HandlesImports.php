<?php

namespace Blueprint\Concerns;

use Blueprint\Contracts\Model;

trait HandlesImports
{
    protected array $imports = [];

    protected function addImport(Model $model, $class): void
    {
        $this->imports[$model->name()][] = $class;
    }

    protected function buildImports(Model $model): string
    {
        return collect($this->imports[$model->name()] ?? [])
            ->map(fn ($class) => "use {$class};")
            ->unique()
            ->sort()
            ->implode(PHP_EOL);
    }
}
