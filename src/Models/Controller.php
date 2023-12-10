<?php

namespace Blueprint\Models;

use Blueprint\Contracts\Model as BlueprintModel;
use Illuminate\Support\Str;

class Controller implements BlueprintModel
{
    public static array $resourceMethods = ['index', 'create', 'store', 'edit', 'update', 'show', 'destroy'];

    public static array $apiResourceMethods = ['index', 'store', 'update', 'show', 'destroy'];

    private string $name;

    private string $namespace;

    private array $methods = [];

    private ?Policy $policy = null;

    private bool $apiResource = false;

    /**
     * Controller constructor.
     */
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
        return $this->name() . (Str::endsWith($this->name(), 'Controller') ? '' : 'Controller');
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

        if (config('blueprint.controllers_namespace')) {
            $fqn .= '\\' . config('blueprint.controllers_namespace');
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

    public function policy(?Policy $policy = null): ?Policy
    {
        if ($policy) {
            $this->policy = $policy;
        }

        return $this->policy;
    }

    public function prefix(): string
    {
        if (Str::endsWith($this->name(), 'Controller')) {
            return Str::substr($this->name(), 0, -10);
        }

        return $this->name();
    }

    public function setApiResource(bool $apiResource): void
    {
        $this->apiResource = $apiResource;
    }

    public function isApiResource(): bool
    {
        return $this->apiResource;
    }
}
