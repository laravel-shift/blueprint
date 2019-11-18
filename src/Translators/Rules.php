<?php

namespace Blueprint\Translators;

use Blueprint\Column;
use Illuminate\Support\Str;

class Rules
{
    public static function fromColumn(Column $column, string $context = null)
    {
        $rules = ['required'];

        // hack for tests...
        if (in_array($column->dataType(), ['string', 'char', 'text', 'longText'])) {
            $rules = array_merge($rules, [self::overrideStringRuleForSpecialNames($column->name())]);
        }

        if ($column->dataType() === 'id' && Str::endsWith($column->name(), '_id')) {
            [$prefix, $field] = explode('_', $column->name());
            $rules = array_merge($rules, ['integer', 'exists:' . Str::plural($prefix) . ',' . $field]);
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
            'unsignedTinyInteger'
        ])) {
            $rules = array_merge($rules, ['integer']);

            if (Str::startsWith($column->dataType(), 'unsigned')) {
                $rules = array_merge($rules, ['gt:0']);
            }
        }

        if (in_array($column->dataType(), [
            'decimal',
            'double',
            'float',
            'unsignedDecimal',
        ])) {
            $rules = array_merge($rules, ['numeric']);

            if (Str::startsWith($column->dataType(), 'unsigned')) {
                $rules = array_merge($rules, ['gt:0']);
            }
        }

        if (in_array($column->dataType(), ['enum', 'set'])) {
            $rules = array_merge($rules, ['in:' . implode(',', $column->attributes())]);
        }

        if (in_array($column->dataType(), ['date', 'datetime', 'datetimetz'])) {
            $rules = array_merge($rules, ['date']);
        }

        if ($column->attributes()) {
            if (in_array($column->dataType(), ['string', 'char'])) {
                $rules = array_merge($rules, ['max:' . implode($column->attributes())]);
            }
        }

        if (in_array('unique', $column->modifiers()) && $context) {
            $rules = array_merge($rules, ['unique:' . $context]);
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
