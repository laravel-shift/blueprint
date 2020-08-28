<?php

namespace Blueprint\Models;

class Index
{
    private $type;
    private $columnNames;

    public function __construct(string $type, array $columnNames = [])
    {
        $this->type = $type;
        $this->columnNames = $columnNames;
    }

    public function type()
    {
        return $this->type;
    }

    public function columnNames()
    {
        return $this->columnNames;
    }
}
