<?php

namespace Blueprint\Models\Statements;

class DispatchStatement
{
    private string $job;

    private array $data;

    public function __construct(string $job, array $data = [])
    {
        $this->job = $job;
        $this->data = $data;
    }

    public function job(): string
    {
        return $this->job;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function output(): string
    {
        $code = $this->job() . '::dispatch(';

        if ($this->data()) {
            $code .= $this->buildParameters($this->data());
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
