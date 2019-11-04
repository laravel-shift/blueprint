<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Model;
use Illuminate\Support\Str;

class MigrationGenerator implements Generator
{
    const INDENT = '            ';

    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->get(STUBS_PATH . '/migration.stub');

        /** @var \Blueprint\Model $model */
        foreach ($tree['models'] as $model) {
            $path = $this->getPath($model);
            $this->files->put(
                $path,
                $this->populateStub($stub, $model)
            );

            $output['created'][] = $path;
        }

        return $output;
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
        return 'database/migrations/' . \Carbon\Carbon::now()->format('Y_m_d_His') . '_create_' . $model->tableName() . '_table.php';
    }
}
