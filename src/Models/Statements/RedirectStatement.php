<?php

namespace Blueprint\Models\Statements;

use Illuminate\Support\Str;

class RedirectStatement
{
    private string $route;

    private array $data;

    public function __construct(string $route, array $data = [])
    {
        $this->route = $route;
        $this->data = $data;
    }

    public function route(): string
    {
        return $this->route;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function output(): string
    {
        $code = "return redirect()->route('" . $this->route() . "'";

        if ($this->data()) {
            $code .= ', [' . $this->buildParameters($this->data()) . ']';
        } elseif (Str::contains($this->route(), '.')) {
            [$model, $method] = explode('.', $this->route());
            if (in_array($method, ['edit', 'update', 'show', 'destroy'])) {
                $model = Str::singular($model);
                $code .= sprintf(", ['%s' => $%s]", $model, $model);
            }
        }

        $code .= ');';

        return $code;
    }

    private function buildParameters(array $data): string
    {
        $parameters = array_map(fn ($parameter) => '$' . $parameter, $data);

        return implode(', ', $parameters);
    }
}
