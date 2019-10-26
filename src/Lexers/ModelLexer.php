<?php

namespace Blueprint\Lexers;

use Blueprint\Column;
use Blueprint\Contracts\Lexer;
use Blueprint\Model;

class ModelLexer implements Lexer
{
    private static $dataTypes = [
        'bigIncrements',
        'bigInteger',
        'binary',
        'boolean',
        'char',
        'date',
        'dateTime',
        'dateTimeTz',
        'decimal',
        'double',
        'enum',
        'float',
        'geometry',
        'geometryCollection',
        'increments',
        'integer',
        'ipAddress',
        'json',
        'jsonb',
        'lineString',
        'longText',
        'macAddress',
        'mediumIncrements',
        'mediumInteger',
        'mediumText',
        'morphs',
        'uuidMorphs',
        'multiLineString',
        'multiPoint',
        'multiPolygon',
        'nullableMorphs',
        'nullableUuidMorphs',
        'nullableTimestamps',
        'point',
        'polygon',
        'rememberToken',
        'set',
        'smallIncrements',
        'smallInteger',
        'softDeletes',
        'softDeletesTz',
        'string',
        'text',
        'time',
        'timeTz',
        'timestamp',
        'timestampTz',
        'timestamps',
        'timestampsTz',
        'tinyIncrements',
        'tinyInteger',
        'unsignedBigInteger',
        'unsignedDecimal',
        'unsignedInteger',
        'unsignedMediumInteger',
        'unsignedSmallInteger',
        'unsignedTinyInteger',
        'uuid',
        'year'
    ];

    private static $modifiers = [
        'autoIncrement',
        'charset',
        'collation',
        'default',
        'nullable',
        'unsigned',
        'useCurrent',
        'always'
    ];

    public function analyze(array $tokens): array
    {
        $registry = [
            'models' => []
        ];

        if (empty($tokens['models'])) {
            return $registry;
        }

        foreach ($tokens['models'] as $name => $definition) {
            $registry['models'][$name] = $this->buildModel($name, $definition);
        }

        return $registry;
    }

    private function buildModel(string $name, array $columns)
    {
        $model = new Model($name);

        if (isset($columns['timestamps'])) {
            if ($columns['timestamps'] === false) {
                $model->disableTimestamps();
            }

            unset($columns['timestamps']);
        }

        if (!isset($columns['id'])) {
            $column = $this->buildColumn('id', 'id');
            $model->addColumn($column);
        }

        foreach ($columns as $name => $definition) {
            $column = $this->buildColumn($name, $definition);
            $model->addColumn($column);
        }

        return $model;
    }

    private function buildColumn(string $name, string $definition)
    {
        $data_type = 'string';
        $modifiers = [];

        $tokens = explode(' ', $definition);
        foreach ($tokens as $token) {
            [$value, $attributes] = explode(':', $token);

            if ($value === 'id') {
                $data_type = 'id';
            } elseif (in_array($value, self::$dataTypes)) {
                $data_type = $value;
                if (!empty($attributes)) {
                    $attributes = explode(',', $attributes);
                }
            }

            if (in_array($value, self::$modifiers)) {
                if (empty($attributes)) {
                    $modifiers[] = $value;
                } else {
                    $modifiers[] = [$value => $attributes];
                    $attributes = [];
                }
            }
        }

        return new Column($name, $data_type, $modifiers, $attributes ?? []);
    }
}