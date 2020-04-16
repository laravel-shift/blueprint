<?php

namespace Blueprint\Translators;

use Blueprint\Models\Column;
use Illuminate\Support\Str;

class Rules
{
    public static function fromColumn(string $context, Column $column)
    {
        $rules = ['required'];

        // hack for tests...
        if (in_array($column->dataType(), ['string', 'char', 'text', 'longText'])) {
            array_push($rules, self::overrideStringRuleForSpecialNames($column->name()));
        }

        if ($column->dataType() === 'id' && Str::endsWith($column->name(), '_id')) {
            [$prefix, $field] = explode('_', $column->name());
            $rules = array_merge($rules, ['integer', 'exists:'.Str::plural($prefix).','.$field]);
        }

        if (in_array($column->dataType(), [
            'integer',
            'tinyInteger',
            'smallInteger',
            'mediumInteger',
            'bigInteger',
            'increments',
            'tinyIncrements',
            'smallIncrements',
            'mediumIncrements',
            'bigIncrements',
            'unsignedBigInteger',
            'unsignedInteger',
            'unsignedMediumInteger',
            'unsignedSmallInteger',
            'unsignedTinyInteger',
        ])) {
            array_push($rules, 'integer');

            if (Str::startsWith($column->dataType(), 'unsigned')) {
                array_push($rules, 'gt:0');
            }
        }

        if (in_array($column->dataType(), [
            'decimal',
            'double',
            'float',
            'unsignedDecimal',
        ])) {
            array_push($rules, 'numeric');

            if (Str::startsWith($column->dataType(), 'unsigned')) {
                array_push($rules, 'gt:0');
            }
        }

        if (in_array($column->dataType(), ['enum', 'set'])) {
            array_push($rules, 'in:'.implode(',', $column->attributes()));
        }

        if (in_array($column->dataType(), ['date', 'datetime', 'datetimetz'])) {
            array_push($rules, 'date');
        }

        if ($column->attributes()) {
            if (in_array($column->dataType(), ['string', 'char'])) {
                array_push($rules, 'max:'.implode($column->attributes()));
            }
        }

        if (in_array('unique', $column->modifiers())) {
            array_push($rules, 'unique:'.$context.','.$column->name());
        }

        return $rules;
    }

    private static function overrideStringRuleForSpecialNames($name)
    {
        if (Str::startsWith($name, 'email')) {
            return 'email';
        }
        if ($name === 'password') {
            return 'password';
        }

        return 'string';
    }
}
