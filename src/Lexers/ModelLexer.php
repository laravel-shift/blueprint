<?php

namespace Blueprint\Lexers;

use Blueprint\Contracts\Lexer;
use Blueprint\Models\Column;
use Blueprint\Models\Index;
use Blueprint\Models\Model;
use Illuminate\Support\Str;

class ModelLexer implements Lexer
{
    private static $relationships = [
        'belongsto' => 'belongsTo',
        'hasone' => 'hasOne',
        'hasmany' => 'hasMany',
        'belongstomany' => 'belongsToMany',
        'morphone' => 'morphOne',
        'morphmany' => 'morphMany',
        'morphto' => 'morphTo',
        'morphtomany' => 'morphToMany',
        'morphedbymany' => 'morphedByMany',
    ];

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
        'fulltext' => 'fullText',
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
        'usecurrentonupdate' => 'useCurrentOnUpdate',
        'always' => 'always',
        'unique' => 'unique',
        'index' => 'index',
        'primary' => 'primary',
        'foreign' => 'foreign',
        'ondelete' => 'onDelete',
        'onupdate' => 'onUpdate',
        'comment' => 'comment',
    ];

    public function analyze(array $tokens): array
    {
        $registry = [
            'models' => [],
            'cache' => [],
        ];

        if (!empty($tokens['models'])) {
            foreach ($tokens['models'] as $name => $definition) {
                $registry['models'][$name] = $this->buildModel($name, $definition);
            }
        }

        if (!empty($tokens['cache'])) {
            foreach ($tokens['cache'] as $name => $definition) {
                $registry['cache'][$name] = $this->buildModel($name, $definition);
            }
        }

        return $registry;
    }

    private function buildModel(string $name, array $columns)
    {
        $model = new Model($name);

        if (isset($columns['meta']) && is_array($columns['meta'])) {
            if (isset($columns['meta']['table'])) {
                $model->setTableName($columns['meta']['table']);
            }

            if (!empty($columns['meta']['pivot'])) {
                $model->setPivot();
            }

            unset($columns['meta']);
        }

        if (isset($columns['id'])) {
            if ($columns['id'] === false) {
                $model->disablePrimaryKey();
                unset($columns['id']);
            }
        }

        if (isset($columns['timestamps'])) {
            if ($columns['timestamps'] === false) {
                $model->disableTimestamps();
            }

            unset($columns['timestamps']);
        } elseif (isset($columns['timestampstz'])) {
            $model->enableTimestamps(true);
            unset($columns['timestampstz']);
        }

        if (isset($columns['softdeletes'])) {
            $model->enableSoftDeletes();
            unset($columns['softdeletes']);
        } elseif (isset($columns['softdeletestz'])) {
            $model->enableSoftDeletes(true);
            unset($columns['softdeletestz']);
        }

        if (isset($columns['relationships'])) {
            if (is_array($columns['relationships'])) {
                foreach ($columns['relationships'] as $type => $relationships) {
                    foreach (explode(',', $relationships) as $reference) {
                        $model->addRelationship(self::$relationships[strtolower($type)], trim($reference));
                    }
                }
            }

            unset($columns['relationships']);
        }

        if (isset($columns['indexes'])) {
            foreach ($columns['indexes'] as $index) {
                $model->addIndex(new Index(key($index), array_map('trim', explode(',', current($index)))));
            }
            unset($columns['indexes']);
        }

        if (!isset($columns['id']) && $model->usesPrimaryKey()) {
            $column = $this->buildColumn('id', 'id');
            $model->addColumn($column);
        }

        foreach ($columns as $name => $definition) {
            $column = $this->buildColumn($name, $definition);
            $model->addColumn($column);

            $foreign = collect($column->modifiers())->filter(fn ($modifier) => collect($modifier)->containsStrict('foreign') || collect($modifier)->has('foreign'))->flatten()->first();

            if (
                ($column->name() !== 'id' && $column->dataType() === 'id')
                || ($column->dataType() === 'uuid' && Str::endsWith($column->name(), '_id'))
                || $foreign
            ) {
                $reference = $column->name();

                if ($foreign && $foreign !== 'foreign') {
                    $table = $foreign;
                    $key = 'id';

                    if (Str::contains($foreign, '.')) {
                        [$table, $key] = explode('.', $foreign);
                    }

                    $reference = Str::singular($table) . ($key === 'id' ? '' : '.' . $key) . ':' . $column->name();
                } elseif ($column->attributes()) {
                    $reference = $column->attributes()[0] . ':' . $column->name();
                }

                $model->addRelationship('belongsTo', $reference);
            }
        }

        return $model;
    }

    private function buildColumn(string $name, string $definition)
    {
        $data_type = null;
        $modifiers = [];

        $tokens = preg_split('#("|\').*?\1(*SKIP)(*FAIL)|\s+#', $definition);
        foreach ($tokens as $token) {
            $parts = explode(':', $token);
            $value = $parts[0];

            if ($value === 'id') {
                $data_type = 'id';
                if (isset($parts[1])) {
                    $attributes = [$parts[1]];
                }
            } elseif (isset(self::$dataTypes[strtolower($value)])) {
                $attributes = $parts[1] ?? null;
                $data_type = self::$dataTypes[strtolower($value)];
                if (!empty($attributes)) {
                    $attributes = explode(',', $attributes);

                    if ($data_type === 'enum') {
                        $attributes = array_map(fn ($attribute) => trim($attribute, '"'), $attributes);
                    }
                }
            }

            if (isset(self::$modifiers[strtolower($value)])) {
                $modifierAttributes = $parts[1] ?? null;
                if (is_null($modifierAttributes)) {
                    $modifiers[] = self::$modifiers[strtolower($value)];
                } else {
                    $modifiers[] = [self::$modifiers[strtolower($value)] => preg_replace('~^[\'"]?(.*?)[\'"]?$~', '$1', $modifierAttributes)];
                }
            }
        }

        if (is_null($data_type)) {
            $is_foreign_key = collect($modifiers)->contains(fn ($modifier) => (is_array($modifier) && key($modifier) === 'foreign') || $modifier === 'foreign');

            $data_type = $is_foreign_key ? 'id' : 'string';
        }

        return new Column($name, $data_type, $modifiers, $attributes ?? []);
    }
}
