<?php

namespace Blueprint\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Generators\AbstractClassGenerator;
use Blueprint\Models\Controller;
use Blueprint\Models\Statements\ValidateStatement;
use Blueprint\Translators\Rules;
use Blueprint\Tree;
use Illuminate\Support\Str;

class FormRequestGenerator extends AbstractClassGenerator implements Generator
{
    public const INDENT = '            ';

    protected array $types = ['controllers', 'requests'];

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $stub = $this->filesystem->stub('request.stub');

        /**
         * @var \Blueprint\Models\Controller $controller
         */
        foreach ($tree->controllers() as $controller) {
            foreach ($controller->methods() as $method => $statements) {
                foreach ($statements as $statement) {
                    if (!$statement instanceof ValidateStatement) {
                        continue;
                    }

                    $context = Str::singular($controller->prefix());
                    $name = $this->getName($context, $method);
                    $path = $this->getStatementPath($controller, $name);

                    if ($this->filesystem->exists($path)) {
                        continue;
                    }

                    $this->create($path, $this->populateStub($stub, $name, $context, $statement, $controller));
                }
            }
        }

        return $this->output;
    }

    protected function getName(string $context, string $method): string
    {
        return $context . Str::studly($method) . 'Request';
    }

    protected function getStatementPath(Controller $controller, string $name): string
    {
        return Blueprint::appPath() . '/Http/Requests/' . ($controller->namespace() ? $controller->namespace() . '/' : '') . $name . '.php';
    }

    protected function populateStub(string $stub, string $name, $context, ValidateStatement $validateStatement, Controller $controller): string
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace') . '\\Http\\Requests' . ($controller->namespace() ? '\\' . $controller->namespace() : ''), $stub);
        $stub = str_replace('{{ class }}', $name, $stub);
        $stub = str_replace('{{ rules }}', $this->buildRules($context, $validateStatement, $controller), $stub);

        return $stub;
    }

    protected function buildRules(string $context, ValidateStatement $validateStatement, Controller $controller): string
    {
        return trim(
            array_reduce(
                $validateStatement->data(),
                function ($output, $field) use ($context, $controller) {
                    [$qualifier, $column] = $this->splitField($field);

                    if (is_null($qualifier)) {
                        $qualifier = $context;
                    }

                    $validationRules = $this->validationRules($qualifier, $column, $controller);

                    foreach ($validationRules as $name => $rule) {
                        $formattedRule = implode("', '", $rule);

                        $output .= self::INDENT . "'{$name}' => ['{$formattedRule}']," . PHP_EOL;
                    }

                    return $output;
                },
                ''
            )
        );
    }

    private function splitField($field): array
    {
        if (Str::contains($field, '.')) {
            return explode('.', $field, 2);
        }

        return [null, $field];
    }

    protected function validationRules(string $qualifier, string $column, Controller $controller): array
    {
        /**
         * @var \Blueprint\Models\Model $model
         */
        $model = $this->tree->modelForContext($qualifier);

        $rules = [];

        if (!is_null($model)) {
            if ($model->hasColumn($column)) {
                $modelColumn = $model->column($column);

                $rules[$column] = Rules::fromColumn($model->tableName(), $modelColumn);

                return $rules;
            } else {
                /**
                 * @var \Blueprint\Models\Column $column
                 */
                foreach ($model->columns() as $column) {
                    if ($column->name() === 'id') {
                        continue;
                    }

                    if ($column->name() === Str::snake($controller->parent()) . '_id') {
                        continue;
                    }

                    $rules[$column->name()] = Rules::fromColumn($model->tableName(), $column);
                }

                return $rules;
            }
        } else {
            $rules[$column] = ['required'];
        }

        return $rules;
    }
}
