<?php

namespace Blueprint\Concerns;

trait HasClassDefinition
{
    private ?string $parent;

    protected array $traits = [];

    protected array $interfaces = [];

    public function parent(): string
    {
        return $this->parent;
    }

    public function usesTraits(): bool
    {
        return \count($this->traits) > 0;
    }

    public function usesInterfaces(): bool
    {
        return \count($this->interfaces) > 0;
    }

    public function setParent(string $class): void
    {
        $this->parent = $class;
    }

    public function interfaces(): array
    {
        return $this->interfaces;
    }

    public function addInterface(string $interface): void
    {
        $this->interfaces[] = $interface;
    }

    public function traits(): array
    {
        return $this->traits;
    }

    public function addTrait(string $trait): void
    {
        $this->traits[] = $trait;
    }
}
