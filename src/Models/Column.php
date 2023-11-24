<?php

namespace Blueprint\Models;

use Illuminate\Support\Str;

class Column
{
    private array $modifiers;

    private string $name;

    private string $dataType;

    private array $attributes;

    public function __construct(string $name, string $dataType = 'string', array $modifiers = [], array $attributes = [])
    {
        $this->name = $name;
        $this->dataType = $dataType;
        $this->modifiers = $modifiers;
        $this->attributes = $attributes;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function dataType(): string
    {
        return $this->dataType;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function modifiers(): array
    {
        return $this->modifiers;
    }

    public function isForeignKey()
    {
        return collect($this->modifiers())->filter(fn ($modifier) => (is_array($modifier) && key($modifier) === 'foreign') || $modifier === 'foreign')->flatten()->first();
    }

    public function defaultValue()
    {
        return collect($this->modifiers())
            ->collapse()
            ->first(fn ($value, $key) => $key === 'default');
    }

    public function isNullable(): bool
    {
        return in_array('nullable', $this->modifiers);
    }

    public function isUnsigned(): bool
    {
        return in_array('unsigned', $this->modifiers);
    }

    public static function columnName($reference): string
    {
        return Str::after($reference, '.');
    }
}
