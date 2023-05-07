<?php

namespace Blueprint\Models;

use Illuminate\Support\Str;

class Column
{
    private $modifiers;

    private $name;

    private $dataType;

    private $attributes;

    public function __construct(string $name, string $dataType = 'string', array $modifiers = [], array $attributes = [])
    {
        $this->name = $name;
        $this->dataType = $dataType;
        $this->modifiers = $modifiers;
        $this->attributes = $attributes;
    }

    public function name()
    {
        return $this->name;
    }

    public function dataType()
    {
        return $this->dataType;
    }

    public function attributes()
    {
        return $this->attributes;
    }

    public function modifiers()
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

    public function isNullable()
    {
        return in_array('nullable', $this->modifiers);
    }

    public function isUnsigned()
    {
        return in_array('unsigned', $this->modifiers);
    }

    public static function columnName($reference)
    {
        return Str::after($reference, '.');
    }
}
