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


        if ($column->attributes()) {
            if (in_array($column->dataType(), ['string', 'char'])) {
                $rules = array_merge($rules, ['max:' . implode($column->attributes())]);
            }
        }

        // if () {
        // $rules = array_merge($rules, ['email']);
        // }

        return $rules;
    }

    private static function overrideStringRuleForSpecialNames($name)
    {
        switch ($name) {
            case Str::startsWith($name, 'email'):
                return 'email';
                break;
            case $name === 'password':
                return 'password';
                break;
            default:
                return 'string';
                break;
        }
    }
}
