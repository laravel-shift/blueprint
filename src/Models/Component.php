<?php

namespace Blueprint\Models;

use Blueprint\Contracts\Model as BlueprintModel;

class Component implements BlueprintModel
{
    private string $name;

    private string $namespace;

    private array $properties = [];

    private array $methods = [];

    public function __construct(string $name)
    {
        $this->name = class_basename($name);
        $this->namespace = trim(implode('\\', array_slice(explode('\\', str_replace('/', '\\', $name)), 0, -1)), '\\');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function className(): string
    {
        return $this->name();
    }

    public function namespace(): string
    {
        if (empty($this->namespace)) {
            return '';
        }

        return $this->namespace;
    }

    public function fullyQualifiedNamespace(): string
    {
        $fqn = config('blueprint.namespace');

        if (config('blueprint.components_namespace')) {
            $fqn .= '\\' . config('blueprint.components_namespace');
        }

        if ($this->namespace) {
            $fqn .= '\\' . $this->namespace;
        }

        return $fqn;
    }

    public function fullyQualifiedClassName(): string
    {
        return $this->fullyQualifiedNamespace() . '\\' . $this->className();
    }

    public function methods(): array
    {
        return $this->methods;
    }

    public function addMethod(string $name, array $statements): void
    {
        $this->methods[$name] = $statements;
    }

    public function properties(): array
    {
        return $this->properties;
    }

    public function addProperty(string $name): void
    {
        $this->properties[$name] = $name;
    }
}
