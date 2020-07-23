<?php

namespace Blueprint;

use Illuminate\Support\Str;

class Tree
{
    private $tree;

    public function __construct(array $tree)
    {
        $this->tree = $tree;

        $this->registerModels();
    }

    private function registerModels()
    {
        $this->models = array_merge($this->tree['cache'] ?? [], $this->tree['models'] ?? []);
    }

    public function controllers()
    {
        return $this->tree['controllers'];
    }

    public function models()
    {
        return $this->tree['models'];
    }

    public function seeders()
    {
        return $this->tree['seeders'];
    }

    public function modelForContext(string $context)
    {
        if (isset($this->models[Str::studly($context)])) {
            return $this->models[Str::studly($context)];
        }

        $matches = array_filter(array_keys($this->models), function ($key) use ($context) {
            return Str::endsWith($key, '/'.Str::studly($context));
        });

        if (count($matches) === 1) {
            return $this->models[$matches[0]];
        }
    }

    public function fqcnForContext(string $context)
    {
        if (isset($this->models[$context])) {
            return $this->models[$context]->fullyQualifiedClassName();
        }

        $matches = array_filter(array_keys($this->models), function ($key) use ($context) {
            return Str::endsWith($key, '\\'.Str::studly($context));
        });

        if (count($matches) === 1) {
            return $this->models[current($matches)]->fullyQualifiedClassName();
        }

        $fqn = config('blueprint.namespace');
        if (config('blueprint.models_namespace')) {
            $fqn .= '\\'.config('blueprint.models_namespace');
        }

        return $fqn.'\\'.$context;
    }

    public function toArray()
    {
        return $this->tree;
    }
}
