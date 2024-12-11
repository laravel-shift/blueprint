<?php

namespace Blueprint\Models\Statements;

use Blueprint\Concerns\HasParameters;

class RenderStatement
{
    use HasParameters;

    private string $view;

    public function __construct(string $view, array $data = [])
    {
        $this->view = $view;
        $this->data = $data;
    }

    public function view(): string
    {
        return $this->view;
    }

    public function output(): string
    {
        $code = "return view('" . $this->view() . "'";

        if ($this->data()) {
            $code .= ', compact(' . $this->buildParameters() . ')';
        }

        $code .= ');';

        return $code;
    }

    private function buildParameters(): string
    {
        $parameters = array_map(fn ($parameter) => "'" . $parameter . "'", $this->data());

        return implode(', ', $parameters);
    }
}
