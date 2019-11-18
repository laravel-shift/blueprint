<?php


namespace Blueprint\Models\Statements;


class RedirectStatement
{
    /**
     * @var string
     */
    private $route;

    /**
     * @var array
     */
    private $data;

    public function __construct(string $route, array $data = [])
    {
        $this->route = $route;
        $this->data = $data;
    }

    public function route()
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }
}