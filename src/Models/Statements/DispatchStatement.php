<?php

namespace Blueprint\Models\Statements;

use Blueprint\Concerns\HasParameters;

class DispatchStatement
{
    use HasParameters;

    private string $job;

    public function __construct(string $job, array $data = [])
    {
        $this->job = $job;
        $this->data = $data;
    }

    public function job(): string
    {
        return $this->job;
    }

    public function output(): string
    {
        $code = $this->job() . '::dispatch(';

        if ($this->data()) {
            $code .= $this->buildParameters();
        }

        $code .= ');';

        return $code;
    }
}
