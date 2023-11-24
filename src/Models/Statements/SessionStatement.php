<?php

namespace Blueprint\Models\Statements;

class SessionStatement
{
    private string $operation;

    private string $reference;

    public function __construct(string $operation, string $reference)
    {
        $this->operation = $operation;
        $this->reference = $reference;
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function output(): string
    {
        $code = '$request->session()->' . $this->operation() . '(';
        $code .= "'" . $this->reference() . "', ";
        $code .= '$' . str_replace('.', '->', $this->reference());
        $code .= ');';

        return $code;
    }
}
