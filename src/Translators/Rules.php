<?php

namespace Blueprint\Translators;

use Blueprint\Column;
use Illuminate\Support\Str;

class Rules
{
    public static function fromColumn(Column $column)
    {
        // TODO: what about nullable?
        $rules = ['required'];

        // TODO: handle translation for...
        // common names (email)
        // relationship (user_id = exists:users,id)
        // dataType (integer,digit,date,etc)
        // attributes (lengths,precisions,enums|set)
        // modifiers (unsigned, nullable, unique)

        // hack for tests...
        if (in_array($column->dataType(), ['string', 'char', 'text', 'longText'])) {
            $rules = array_merge($rules, [self::overrideStringRuleForSpecialNames($column->name())]);
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
