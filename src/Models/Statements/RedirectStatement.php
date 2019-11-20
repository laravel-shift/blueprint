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


    public function output()
    {
        $code = "return redirect()->route('" . $this->route() . "'";

        if ($this->data()) {
            $code .= ', [' . $this->buildParameters($this->data()) . ']';
        }

        $code .= ');';

        return $code;
    }

    private function buildParameters(array $data)
    {
        $parameters = array_map(function ($parameter) {
            return '$' . $parameter;
        }, $data);

        return implode(', ', $parameters);
    }
}