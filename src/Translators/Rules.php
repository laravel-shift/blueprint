<?php

namespace Blueprint\Translators;

use Illuminate\Support\Str;
use Blueprint\Models\Column;

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
            'unsignedTinyInteger',
        ])) {
            array_push($rules, 'integer');

            if (Str::startsWith($column->dataType(), 'unsigned')) {
                array_push($rules, 'gt:0');
            }
        }

        if (in_array($column->dataType(), ['json'])) {
            array_push($rules, 'json');
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
            array_push($rules, 'in:' . implode(',', $column->attributes()));
        }

        if (in_array($column->dataType(), ['date', 'datetime', 'datetimetz'])) {
            array_push($rules, 'date');
        }

        if ($column->attributes()) {
            if (in_array($column->dataType(), ['string', 'char'])) {
                array_push($rules, 'max:' . implode($column->attributes()));
            }
        }

        if (in_array('unique', $column->modifiers())) {
            array_push($rules, 'unique:' . $context . ',' . $column->name());
        }

        if (Str::contains($column->dataType(), 'decimal')) {
            array_push($rules, self::betweenRuleForNumericTypes($column, 'decimal'));
        }

        if (Str::contains($column->dataType(), 'float')) {
            array_push($rules, self::betweenRuleForNumericTypes($column, 'float'));
        }

        if (Str::contains($column->dataType(), 'double')) {
            array_push($rules, self::betweenRuleForNumericTypes($column, 'double'));
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


    private static function betweenRuleForNumericTypes(Column $column, $numericType)
    {
        $parameters = explode(",", Str::between($column->dataType(), "$numericType:", " "));

        if (count($parameters) === 1) {
            return;
        }

        [$precision, $scale] = $parameters;

        $max = substr_replace(str_pad("", $precision, '9'), ".", $precision - $scale, 0);
        $min = "-" . $max;

        if (intval($scale) === 0) {
            $min = trim($min, ".");
            $max = trim($max, ".");
        }

        if (Str::contains($column->dataType(), 'unsigned')) {
            $min = '0';
        }

        $betweenRule = 'between:' . $min . ',' . $max;

        return $betweenRule;
    }
}
