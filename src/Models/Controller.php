<?php


namespace Blueprint\Models;

use Illuminate\Support\Str;

class Controller
{
    /** @var array */
    public static $resourceMethods = ['index', 'create', 'store', 'edit', 'update', 'show', 'destroy'];

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

    /** @var bool */
    private $is_api = false;

    /**
     * Controller constructor.
     * @param $name
     */
    public function __construct(string $name, bool $is_api)
    {
        $this->name = class_basename($name);
        $this->is_api = $is_api;
        $this->namespace = trim(implode('\\', array_slice(explode('\\', str_replace('/', '\\', $name)), 0, -1)), '\\');
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isAPI(): bool
    {
        return $this->is_api;
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

    public function setAPI(bool $is_api = true)
    {
        $this->is_api = $is_api;
    }

    public function prefix()
    {
        if (Str::endsWith($this->name(), 'Controller')) {
            return Str::substr($this->name(), 0, -10);
        }

        return $this->name();
    }
}
