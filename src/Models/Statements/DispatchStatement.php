<?php

namespace Blueprint\Models\Statements;

class DispatchStatement
{
    /**
     * @var string
     */
    private $job;

    /**
     * @var array
     */
    private $data;

    public function __construct(string $job, array $data = [])
    {
        $this->job = $job;
        $this->data = $data;
    }

    public function job()
    {
        return $this->job;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function output()
    {
        $code = $this->job() . '::dispatch(';

        if ($this->data()) {
            $code .= $this->buildParameters($this->data());
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
