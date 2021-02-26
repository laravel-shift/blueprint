<?php

namespace Blueprint\Models\Statements;

class RenderStatement
{
    /**
     * @var string
     */
    private $view;

    /**
     * @var array
     */
    private $data;

    public function __construct(string $view, array $data = [])
    {
        $this->view = $view;
        $this->data = $data;
    }

    public function view()
    {
        return $this->view;
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
        $code = "return view('" . $this->view() . "'";

        if ($this->data()) {
            $code .= ', compact(' . $this->buildParameters($this->data()) . ')';
        }

        $code .= ');';

        return $code;
    }

    private function buildParameters(array $data)
    {
        $parameters = array_map(function ($parameter) {
            return "'" . $parameter . "'";
        }, $data);

        return implode(', ', $parameters);
    }
}
