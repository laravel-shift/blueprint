<?php

namespace Blueprint\Lexers;

use Blueprint\Column;
use Blueprint\Contracts\Lexer;
use Blueprint\Model;

class ModelLexer implements Lexer
{
    private static $dataTypes = [
        'bigincrements' => 'bigIncrements',
        'biginteger' => 'bigInteger',
        'binary' => 'binary',
        'boolean' => 'boolean',
        'char' => 'char',
        'date' => 'date',
        'datetime' => 'dateTime',
        'datetimetz' => 'dateTimeTz',
        'decimal' => 'decimal',
        'double' => 'double',
        'enum' => 'enum',
        'float' => 'float',
        'geometry' => 'geometry',
        'geometrycollection' => 'geometryCollection',
        'increments' => 'increments',
        'integer' => 'integer',
        'ipaddress' => 'ipAddress',
        'json' => 'json',
        'jsonb' => 'jsonb',
        'linestring' => 'lineString',
        'longtext' => 'longText',
        'macaddress' => 'macAddress',
        'mediumincrements' => 'mediumIncrements',
        'mediuminteger' => 'mediumInteger',
        'mediumtext' => 'mediumText',
        'morphs' => 'morphs',
        'uuidmorphs' => 'uuidMorphs',
        'multilinestring' => 'multiLineString',
        'multipoint' => 'multiPoint',
        'multipolygon' => 'multiPolygon',
        'nullablemorphs' => 'nullableMorphs',
        'nullableuuidmorphs' => 'nullableUuidMorphs',
        'nullabletimestamps' => 'nullableTimestamps',
        'point' => 'point',
        'polygon' => 'polygon',
        'remembertoken' => 'rememberToken',
        'set' => 'set',
        'smallincrements' => 'smallIncrements',
        'smallinteger' => 'smallInteger',
        'softdeletes' => 'softDeletes',
        'softdeletestz' => 'softDeletesTz',
        'string' => 'string',
        'text' => 'text',
        'time' => 'time',
        'timetz' => 'timeTz',
        'timestamp' => 'timestamp',
        'timestamptz' => 'timestampTz',
        'timestamps' => 'timestamps',
        'timestampstz' => 'timestampsTz',
        'tinyincrements' => 'tinyIncrements',
        'tinyinteger' => 'tinyInteger',
        'unsignedbiginteger' => 'unsignedBigInteger',
        'unsigneddecimal' => 'unsignedDecimal',
        'unsignedinteger' => 'unsignedInteger',
        'unsignedmediuminteger' => 'unsignedMediumInteger',
        'unsignedsmallinteger' => 'unsignedSmallInteger',
        'unsignedtinyinteger' => 'unsignedTinyInteger',
        'uuid' => 'uuid',
        'year' => 'year',
    ];

    private static $modifiers = [
        'autoincrement' => 'autoIncrement',
        'charset' => 'charset',
        'collation' => 'collation',
        'default' => 'default',
        'nullable' => 'nullable',
        'unsigned' => 'unsigned',
        'usecurrent' => 'useCurrent',
        'always' => 'always',
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
            $parts = explode(':', $token);
            $value = $parts[0];
            $attributes = $parts[1] ?? null;

            if ($value === 'id') {
                $data_type = 'id';
            } elseif (isset(self::$dataTypes[strtolower($value)])) {
                $data_type = self::$dataTypes[strtolower($value)];
                if (!empty($attributes)) {
                    $attributes = explode(',', $attributes);
                }
            }

            if (isset(self::$modifiers[strtolower($value)])) {
                if (empty($attributes)) {
                    $modifiers[] = self::$modifiers[strtolower($value)];
                } else {
                    $modifiers[] = [self::$modifiers[strtolower($value)] => $attributes];
                    $attributes = [];
                }
            }
        }

        return new Column($name, $data_type, $modifiers, $attributes ?? []);
    }
}