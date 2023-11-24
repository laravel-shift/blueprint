<?php

namespace Blueprint\Models\Statements;

class RenderStatement
{
    private string $view;

    private array $data;

    public function __construct(string $view, array $data = [])
    {
        $this->view = $view;
        $this->data = $data;
    }

    public function view(): string
    {
        return $this->view;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function output(): string
    {
        $code = "return view('" . $this->view() . "'";

        if ($this->data()) {
            $code .= ', compact(' . $this->buildParameters($this->data()) . ')';
        }

        $code .= ');';

        return $code;
    }

    private function buildParameters(array $data): string
    {
        $parameters = array_map(fn ($parameter) => "'" . $parameter . "'", $data);

        return implode(', ', $parameters);
    }
}
