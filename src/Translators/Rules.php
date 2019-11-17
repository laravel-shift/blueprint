<?php

namespace Blueprint\Translators;

use Blueprint\Column;

class Rules
{
    public static function fromColumn(Column $column)
    {
        // TODO: post v1 figure out how to handle nullable Rule
        $rules = ['required'];

        // TODO: handle translation for...
        // common names (email)
        // relationship (user_id = exists:users,id)
        // dataType (integer,digit,date,etc)
        // attributes (lengths,precisions,enums|set)
        // modifiers (unsigned, nullable, unique)

        if (in_array('email', explode('_', $column->name()))) {
            $rules = array_merge($rules, ['email']);
        }

        $rules = self::createRulesForStringableDataTypes($column, $rules);


        return $rules;
    }

    private function createRulesForStringableDataTypes($column, $rules)
    {
        if (in_array('email', $rules)) {
            return $rules;
        }

        if (in_array($column->dataType(), ['string', 'char', 'text', 'longText'])) {
            $rules = array_merge($rules, ['string']);
        }

        if ($column->attributes()) {
            if (in_array($column->dataType(), ['string', 'char'])) {
                $rules = array_merge($rules, ['max:' . implode($column->attributes())]);
            }
        }

        return $rules;
    }
}
