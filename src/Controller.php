<?php


namespace Blueprint;


class Controller
{
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

    public function methods(): array
    {
        return $this->methods;
    }

    public function addMethod(string $name, array $statements)
    {
        $this->methods[$name] = $statements;
    }
}