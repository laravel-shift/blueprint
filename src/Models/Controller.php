<?php

namespace Blueprint\Models;

use Blueprint\Contracts\Model as BlueprintModel;
use Illuminate\Support\Str;

class Controller implements BlueprintModel
{
    /** @var array */
    public static $resourceMethods = ['index', 'create', 'store', 'edit', 'update', 'show', 'destroy'];

    /** @var array */
    public static $apiResourceMethods = ['index', 'store', 'update', 'show', 'destroy'];

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var array
     */
    private $methods = [];

    /**
     * @var Policy
     */
    private $policy;

    /**
     * @var bool
     */
    private $apiResource = false;

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

    public function namespace()
    {
        if (empty($this->namespace)) {
            return '';
        }

        return $this->namespace;
    }

    public function fullyQualifiedNamespace()
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

    public function fullyQualifiedClassName()
    {
        return $this->fullyQualifiedNamespace() . '\\' . $this->className();
    }

    public function methods(): array
    {
        return $this->methods;
    }

    public function addMethod(string $name, array $statements)
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

    public function prefix()
    {
        if (Str::endsWith($this->name(), 'Controller')) {
            return Str::substr($this->name(), 0, -10);
        }

        return $this->name();
    }

    public function setApiResource(bool $apiResource)
    {
        $this->apiResource = $apiResource;
    }

    public function isApiResource(): bool
    {
        return $this->apiResource;
    }
}
