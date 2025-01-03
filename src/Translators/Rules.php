<?php

namespace Blueprint\Translators;

use Blueprint\Models\Column;
use Illuminate\Support\Str;

class Rules
{
    const INTEGER_TYPES = [
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
    ];

    public static function fromColumn(string $context, Column $column): array
    {
        $rules = [];

        array_push($rules, in_array('nullable', $column->modifiers()) ? 'nullable' : 'required');

        // hack for tests...
        if (in_array($column->dataType(), ['string', 'char', 'text', 'longText', 'fullText'])) {
            array_push($rules, self::overrideStringRuleForSpecialNames($column->name()));
        }

        if ($column->dataType() === 'id' && ($column->attributes() || Str::endsWith($column->name(), '_id'))) {
            $table = $column->modifiers()[0]['foreign'] ?? Str::plural($column->attributes()[0] ?? Str::beforeLast($column->name(), '_id'));
            $rules = array_merge($rules, ['integer', 'exists:' . $table . ',id']);
        }

        if (in_array($column->dataType(), self::INTEGER_TYPES)) {
            array_push($rules, 'integer');

            if (Str::startsWith($column->dataType(), 'unsigned')) {
                array_push($rules, 'gt:0');
            }
        }

        if (in_array($column->dataType(), ['json'])) {
            array_push($rules, 'json');
        }

        if (in_array($column->dataType(), ['decimal', 'double', 'float', 'unsignedDecimal'])) {
            array_push($rules, 'numeric');

            if (Str::startsWith($column->dataType(), 'unsigned') || in_array('unsigned', $column->modifiers())) {
                array_push($rules, 'gt:0');
            }

            if (!empty($column->attributes())) {
                array_push($rules, self::betweenRuleForColumn($column));
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

        return $rules;
    }

    private static function overrideStringRuleForSpecialNames($name): string
    {
        if (Str::startsWith($name, 'email')) {
            return 'email';
        }
        if ($name === 'password') {
            return 'password';
        }

        return 'string';
    }

    private static function betweenRuleForColumn(Column $column): string
    {
        $precision = $column->attributes()[0];
        $scale = $column->attributes()[1] ?? 0;

        $value = substr_replace(str_pad('', $precision, '9'), '.', $precision - $scale, 0);

        if (intval($scale) === 0) {
            $value = trim($value, '.');
        }

        if ($precision == $scale) {
            $value = '0' . $value;
        }

        $min = $column->dataType() === 'unsignedDecimal' || in_array('unsigned', $column->modifiers()) ? '0' : '-' . $value;

        return 'between:' . $min . ',' . $value;
    }
}
