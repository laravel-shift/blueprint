<?php

namespace Blueprint;

use Illuminate\Support\Str;

class Tree
{
    private array $tree;

    private array $models = [];

    public function __construct(array $tree)
    {
        $this->tree = $tree;

        $this->registerModels();
    }

    private function registerModels(): void
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

    public function policies()
    {
        return $this->tree['policies'];
    }

    public function seeders()
    {
        return $this->tree['seeders'];
    }

    public function modelForContext(string $context, bool $throw = false)
    {
        if (isset($this->models[Str::studly($context)])) {
            return $this->models[Str::studly($context)];
        }

        if (isset($this->models[Str::studly(Str::plural($context))])) {
            return $this->models[Str::studly(Str::plural($context))];
        }

        $matches = array_filter(
            array_keys($this->models),
            fn ($key) => Str::endsWith(
                Str::afterLast(Str::afterLast($key, '\\'), '/'),
                [Str::studly($context), Str::studly(Str::plural($context))]
            )
        );

        if (count($matches) !== 1) {
            if ($throw) {
                throw new \InvalidArgumentException(sprintf('The model class [%s] could not be found.', $this->fqcnForContext($context)));
            }

            return null;
        }

        return $this->models[current($matches)];
    }

    public function fqcnForContext(string $context): string
    {
        if (isset($this->models[$context])) {
            return $this->models[$context]->fullyQualifiedClassName();
        }

        $matches = array_filter(array_keys($this->models), fn ($key) => Str::endsWith($key, '\\' . Str::studly($context)));

        if (count($matches) === 1) {
            return $this->models[current($matches)]->fullyQualifiedClassName();
        }

        $fqn = config('blueprint.namespace');
        if (config('blueprint.models_namespace')) {
            $fqn .= '\\' . config('blueprint.models_namespace');
        }

        return $fqn . '\\' . $context;
    }

    public function toArray(): array
    {
        return $this->tree;
    }
}
