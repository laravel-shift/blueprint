<?php

namespace Blueprint\Models\Statements;

use Illuminate\Support\Str;

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

    public function data(): array
    {
        return $this->data;
    }

    public function output()
    {
        $code = "return redirect()->route('" . $this->route() . "'";

        if ($this->data()) {
            $code .= ', [' . $this->buildParameters($this->data()) . ']';
        } elseif (Str::contains($this->route(), '.')) {
            [$model, $method] = explode('.', $this->route());
            if (in_array($method, ['edit', 'update', 'show', 'destroy'])) {
                $code .= sprintf(", ['%s' => $%s]", $model, $model);
            }
        }

        $code .= ');';

        return $code;
    }

    private function buildParameters(array $data)
    {
        $parameters = array_map(fn ($parameter) => '$' . $parameter, $data);

        return implode(', ', $parameters);
    }
}
