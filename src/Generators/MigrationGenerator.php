<?php

namespace Blueprint\Generators;

use Blueprint\Model;
use Illuminate\Support\Str;

class MigrationGenerator
{
    public function output(array $tree)
    {
        // TODO: what if changing an existing model
        $stub = file_get_contents('stubs/migration.stub');

        /** @var \Blueprint\Model $model */
        foreach ($tree['models'] as $model) {
            file_put_contents(
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
            $definition .= '$table->' . $column->dataType() . "('{$column->name()}'";
            if (!empty($column->attributes())) {
                // TODO: what about set and enum?
                $definition .= ', ' . implode(', ', $column->attributes());
            }
            $definition .= ')';

            foreach ($column->modifiers() as $modifier) {
                if (is_array($modifier)) {
                    // TODO: properly handle quoted values
                    $definition .= "->" . key($modifier) . "(" . current($modifier) . ")";
                } else {
                    $definition .= '->' . $modifier . '()';
                }
            }

            $definition .= ';' . PHP_EOL;
        }

        if ($model->usesTimestamps()) {
            $definition .= '$table->timestamps();' . PHP_EOL;
        }

        return trim($definition);
    }

    protected function getClassName(Model $model)
    {
        return 'Create' . Str::studly($model->tableName()) . 'Table';
    }

    protected function getPath(Model $model)
    {
        return 'build/' . date('Y_m_d_His') . '_create_' . $model->tableName() . '_table.php';
    }
}