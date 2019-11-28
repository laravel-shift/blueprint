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
     * @var array
     */
    private $methods = [];

    /**
     * Controller constructor.
     * @param $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function className(): string
    {
        return $this->name() . (Str::endsWith($this->name(), 'Controller') ? '' : 'Controller');
    }

    public function methods(): array
    {
        return $this->methods;
    }

    public function addMethod(string $name, array $statements)
    {
        $this->methods[$name] = $statements;
    }

    public function prefix()
    {
        if (Str::endsWith($this->name(), 'Controller')) {
            return Str::substr($this->name(), 0, -10);
        }

        return $this->name();
    }
}