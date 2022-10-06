<?php

namespace Blueprint\Models;

class Index
{
    private $type;

    private $columns;

    public function __construct(string $type, array $columns = [])
    {
        $this->type = $type;
        $this->columns = $columns;
    }

    public function type()
    {
        return $this->type;
    }

    public function columns()
    {
        return $this->columns;
    }
}
