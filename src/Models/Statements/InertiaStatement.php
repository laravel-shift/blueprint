<?php

namespace Blueprint\Models\Statements;

use Blueprint\Concerns\HasParameters;

class InertiaStatement
{
    use HasParameters;

    private string $view;

    public function __construct(string $view, array $data = [])
    {
        $this->view = $view;
        $this->data = $data;
    }

    public function output(): string
    {
        $code = "return Inertia::render('" . $this->view() . "'";

        if ($this->data()) {
            $code .= ', ' . $this->buildParameters();
        }

        $code .= ');';

        return $code;
    }

    public function view(): string
    {
        return $this->view;
    }

    private function buildParameters(): string
    {
        $parameters = array_map(
            fn ($parameter) => sprintf(
                "%s'%s' => \$%s%s,%s",
                str_pad(' ', 12),
                $parameter,
                in_array($parameter, $this->properties()) ? 'this->' : '',
                $parameter,
                PHP_EOL
            ),
            $this->data()
        );

        return sprintf(
            '[%s%s%s]',
            PHP_EOL,
            implode($parameters),
            str_pad(' ', 8)
        );
    }
}
