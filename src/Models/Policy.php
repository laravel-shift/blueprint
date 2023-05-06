<?php

namespace Blueprint\Models;

use Blueprint\Contracts\Model as BlueprintModel;
use Illuminate\Support\Str;

class Policy implements BlueprintModel
{
    /** @var array */
    public static $supportedMethods = [
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
        'restore',
        'forceDelete',
    ];

    /** @var array */
    public static $resourceAbilityMap = [
        'index' => 'viewAny',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'destroy' => 'delete',
    ];

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var array<int, string>
     */
    private $methods;

    /**
     * @var bool
     */
    private $authorizeResource;

    /**
     * Controller constructor.
     */
    public function __construct(string $name, array $methods, bool $authorizeResource)
    {
        $this->name = class_basename($name);
        $this->namespace = trim(implode('\\', array_slice(explode('\\', str_replace('/', '\\', $name)), 0, -1)), '\\');
        $this->methods = $methods;
        $this->authorizeResource = $authorizeResource;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function className(): string
    {
        return $this->name() . (Str::endsWith($this->name(), 'Policy') ? '' : 'Policy');
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

        if (config('blueprint.policy_namespace')) {
            $fqn .= '\\' . config('blueprint.policy_namespace');
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

    public function authorizeResource(): bool
    {
        return $this->authorizeResource;
    }

    public function fullyQualifiedModelClassName()
    {
        $fqn = config('blueprint.namespace');

        if (config('blueprint.models_namespace')) {
            $fqn .= '\\' . config('blueprint.models_namespace');
        }

        if ($this->namespace) {
            $fqn .= '\\' . $this->namespace;
        }

        return $fqn . '\\' . $this->name;
    }
}
