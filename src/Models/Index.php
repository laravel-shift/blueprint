<?php

namespace Blueprint\Models;

class Index
{
    private string $type;

    private array $columns;

    public function __construct(string $type, array $columns = [])
    {
        $this->type = $type;
        $this->columns = $columns;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function columns(): array
    {
        return $this->columns;
    }
}
