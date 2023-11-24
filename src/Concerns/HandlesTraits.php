<?php

namespace Blueprint\Concerns;

use Blueprint\Contracts\Model;

trait HandlesTraits
{
    protected array $traits = [];

    protected function addTrait(Model $model, $trait): void
    {
        $this->traits[$model->name()][] = $trait;
    }

    protected function buildTraits(Model $model): string
    {
        if (empty($this->traits[$model->name()])) {
            return '';
        }

        $traits = collect($this->traits[$model->name()])
            ->unique()
            ->sort()
            ->implode(', ');

        return "use {$traits};";
    }
}
