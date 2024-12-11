<?php

namespace Blueprint\Concerns;

trait HasParameters
{
    protected array $data = [];

    protected array $properties = [];

    public function data(): array
    {
        return $this->data;
    }

    public function properties(): array
    {
        return $this->properties;
    }

    public function withProperties(array $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    protected function buildParameters(): string
    {
        $parameters = array_map(fn ($parameter) => in_array($parameter, $this->properties()) ? '$this->' . $parameter : '$' . $parameter, $this->data());

        return implode(', ', $parameters);
    }
}
