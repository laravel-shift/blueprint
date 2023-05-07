<?php

namespace Blueprint\Models\Statements;

class FireStatement
{
    /**
     * @var string
     */
    private $event;

    /**
     * @var array
     */
    private $data;

    public function __construct(string $event, array $data = [])
    {
        $this->event = $event;
        $this->data = $data;
    }

    public function event()
    {
        return $this->event;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function isNamedEvent(): bool
    {
        return preg_match('/^[a-z0-9.]+$/', $this->event) === 1;
    }

    public function output()
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
            $this->data() ? $this->buildParameters($this->data()) : ''
        );
    }

    private function buildParameters(array $data)
    {
        $parameters = array_map(fn ($parameter) => '$' . $parameter, $data);

        return implode(', ', $parameters);
    }
}
