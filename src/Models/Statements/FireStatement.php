<?php

namespace Blueprint\Models\Statements;

use Blueprint\Concerns\HasParameters;

class FireStatement
{
    use HasParameters;

    private string $event;

    public function __construct(string $event, array $data = [])
    {
        $this->event = $event;
        $this->data = $data;
    }

    public function event(): string
    {
        return $this->event;
    }

    public function isNamedEvent(): bool
    {
        return preg_match('/^[a-z0-9.]+$/', $this->event) === 1;
    }

    public function output(): string
    {
        $template = '%s::dispatch(%s);';

        if ($this->isNamedEvent()) {
            if ($this->data()) {
                $template = "event('%s', [%s]);";
            } else {
                $template = "event('%s');";
            }
        }

        return sprintf(
            $template,
            $this->event(),
            $this->data() ? $this->buildParameters() : ''
        );
    }
}
