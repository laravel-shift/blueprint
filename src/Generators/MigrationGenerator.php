<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationGenerator implements Generator
{
    const INDENT = '            ';

    public function output(array $tree): void
    {
        // TODO: what if changing an existing model
        $stub = File::get('stubs/migration.stub');

        /** @var \Blueprint\Model $model */
        foreach ($tree['models'] as $model) {
            File::put(
                $this->getPath($model),
                $this->populateStub($stub, $model)
            );
        }
    }

    protected function populateStub(string $stub, Model $model)
    {
        $stub = str_replace('DummyClass', $this->getClassName($model), $stub);
        $stub = str_replace('DummyTable', $model->tableName(), $stub);
        $stub = str_replace('// definition...', $this->buildDefinition($model), $stub);

        return $stub;
    }

    protected function buildDefinition(Model $model)
    {
        $definition = '';

        /** @var \Blueprint\Column $column */
        foreach ($model->columns() as $column) {
            $dataType = $column->dataType();
            if ($column->name() === 'id') {
                $dataType = 'increments';
            } elseif ($column->dataType() === 'id') {
                $dataType = 'unsignedBigInteger';
            }

            $definition .= self::INDENT . '$table->' . $dataType . "('{$column->name()}'";

            if (!empty($column->attributes())) {
                $definition .= ', ';
                if (in_array($column->dataType(), ['set', 'enum'])) {
                    $definition .= json_encode($column->attributes());
                } else {
                    $definition .= implode(', ', $column->attributes());
                }
            }
            $definition .= ')';

            foreach ($column->modifiers() as $modifier) {
                if (is_array($modifier)) {
                    $definition .= "->" . key($modifier) . "(" . current($modifier) . ")";
                } else {
                    $definition .= '->' . $modifier . '()';
                }
            }

            $definition .= ';' . PHP_EOL;
        }

        if ($model->usesTimestamps()) {
            $definition .= self::INDENT . '$table->timestamps();' . PHP_EOL;
        }

        return trim($definition);
    }

    protected function getClassName(Model $model)
    {
        return 'Create' . Str::studly($model->tableName()) . 'Table';
    }

    protected function getPath(Model $model)
    {
        return 'build/' . \Carbon\Carbon::now()->format('Y_m_d_His') . '_create_' . $model->tableName() . '_table.php';
    }
}