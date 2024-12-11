<?php

namespace Blueprint\Models\Statements;

use Illuminate\Support\Str;

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

    public function output(array $properties = [], bool $livewire = false): string
    {
        $template = "%ssession()->%s('%s', %s);";

        return sprintf(
            $template,
            $livewire ? '' : '$request->',
            $this->operation(),
            $this->reference(),
            $this->buildValue($properties)
        );
    }

    private function buildValue(array $properties): string
    {
        $variable = str_replace('.', '->', $this->reference());

        if (in_array(Str::before($this->reference(), '.'), $properties)) {
            $variable = 'this->' . $variable;
        }

        return '$' . $variable;
    }
}
