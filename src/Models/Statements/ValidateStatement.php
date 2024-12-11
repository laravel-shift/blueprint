<?php

namespace Blueprint\Models\Statements;

use Blueprint\Concerns\HasParameters;

class ValidateStatement
{
    use HasParameters;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
