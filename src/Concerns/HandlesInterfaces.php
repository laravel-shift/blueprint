<?php

namespace Blueprint\Concerns;

use Blueprint\Contracts\Model;

trait HandlesInterfaces
{
    protected array $interfaces = [];

    protected function addInterface(Model $model, $interface): void
    {
        $this->interfaces[$model->name()][] = $interface;
    }

    protected function buildInterfaces(Model $model): string
    {
        if (empty($this->interfaces[$model->name()])) {
            return '';
        }

        $traits = collect($this->interfaces[$model->name()])
            ->unique()
            ->sort()
            ->implode(', ');

        return " implements {$traits}";
    }
}
