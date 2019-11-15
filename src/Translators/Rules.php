<?php

namespace Blueprint\Translators;

use Blueprint\Column;

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