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
    protected $types = ['controllers', 'requests'];

    public const INDENT = '            ';

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

    protected function getStatementPath(Controller $controller, string $name)
    {
        return Blueprint::appPath() . '/Http/Requests/' . ($controller->namespace() ? $controller->namespace() . '/' : '') . $name . '.php';
    }

    protected function populateStub(string $stub, string $name, $context, ValidateStatement $validateStatement, Controller $controller)
    {
        $stub = str_replace('{{ namespace }}', config('blueprint.namespace') . '\\Http\\Requests' . ($controller->namespace() ? '\\' . $controller->namespace() : ''), $stub);
        $stub = str_replace('{{ class }}', $name, $stub);
        $stub = str_replace('{{ rules }}', $this->buildRules($context, $validateStatement), $stub);

        return $stub;
    }

    protected function buildRules(string $context, ValidateStatement $validateStatement)
    {
        return trim(
            array_reduce(
                $validateStatement->data(),
                function ($output, $field) use ($context) {
                    [$qualifier, $column] = $this->splitField($field);

                    if (is_null($qualifier)) {
                        $qualifier = $context;
                    }

                    $validationRules = $this->validationRules($qualifier, $column);

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

    private function getName(string $context, string $method)
    {
        return $context . Str::studly($method) . 'Request';
    }

    private function splitField($field)
    {
        if (Str::contains($field, '.')) {
            return explode('.', $field, 2);
        }

        return [null, $field];
    }

    private function validationRules(string $qualifier, string $column)
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
                 * @var \Blueprint\Models\Model $column
                 */
                foreach ($model->columns() as $column) {
                    if ($column->name() === 'id') {
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
