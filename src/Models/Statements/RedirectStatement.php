<?php

namespace Blueprint\Models\Statements;

use Blueprint\Concerns\HasParameters;
use Illuminate\Support\Str;

class RedirectStatement
{
    use HasParameters;

    private string $route;

    public function __construct(string $route, array $data = [])
    {
        $this->route = $route;
        $this->data = $data;
    }

    public function route(): string
    {
        return $this->route;
    }

    public function output(): string
    {
        $code = "return redirect()->route('" . $this->route() . "'";

        if ($this->data()) {
            $code .= ', [' . $this->buildParameters() . ']';
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
}
