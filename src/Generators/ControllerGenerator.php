<?php

namespace Blueprint\Generators;

use Blueprint\Concerns\HandlesImports;
use Blueprint\Concerns\HandlesTraits;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Models\Policy;
use Blueprint\Models\Statements\DispatchStatement;
use Blueprint\Models\Statements\EloquentStatement;
use Blueprint\Models\Statements\FireStatement;
use Blueprint\Models\Statements\QueryStatement;
use Blueprint\Models\Statements\RedirectStatement;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Models\Statements\ResourceStatement;
use Blueprint\Models\Statements\RespondStatement;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Models\Statements\SessionStatement;
use Blueprint\Models\Statements\ValidateStatement;
use Blueprint\Tree;
use Illuminate\Support\Str;

class ControllerGenerator extends AbstractClassGenerator implements Generator
{
    use HandlesImports, HandlesTraits;

    protected $types = ['controllers'];

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $stub = $this->filesystem->stub('controller.class.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree->controllers() as $controller) {
            $this->addImport($controller, 'Illuminate\\Http\\Request');
            if ($controller->fullyQualifiedNamespace() !== 'App\\Http\\Controllers') {
                $this->addImport($controller, 'App\\Http\\Controllers\\Controller');
            }
            $path = $this->getPath($controller);

            $this->create($path, $this->populateStub($stub, $controller));
        }

        return $this->output;
    }

    protected function populateStub(string $stub, Controller $controller)
    {
        $stub = str_replace('{{ namespace }}', $controller->fullyQualifiedNamespace(), $stub);
        $stub = str_replace('{{ class }}', $controller->className(), $stub);
        $stub = str_replace('{{ methods }}', $this->buildMethods($controller), $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($controller), $stub);

        return $stub;
    }

    protected function buildMethods(Controller $controller)
    {
        $template = $this->filesystem->stub('controller.method.stub');

        $methods = '';

        $controllerModelName = Str::singular($controller->prefix());

        if ($controller->policy()?->authorizeResource()) {
            $methods .= str_replace(
                [
                    '{{ modelClass }}',
                    '{{ modelVariable }}',
                ],
                [
                    Str::studly($controllerModelName),
                    Str::camel($controllerModelName),
                ],
                $this->filesystem->stub('controller.authorize-resource.stub')
            );
        }

        foreach ($controller->methods() as $name => $statements) {
            $method = str_replace('{{ method }}', $name, $template);

            if (in_array($name, ['edit', 'update', 'show', 'destroy'])) {
                $reference = $this->fullyQualifyModelReference($controller->namespace(), $controllerModelName);
                $variable = '$' . Str::camel($controllerModelName);

                $search = '(Request $request';
                $method = str_replace($search, $search . ', ' . $controllerModelName . ' ' . $variable, $method);
                $this->addImport($controller, $reference);
            }

            $body = '';
            $using_validation = false;

            if ($controller->policy() && !$controller->policy()->authorizeResource()) {
                if (in_array(Policy::$resourceAbilityMap[$name], $controller->policy()->methods())) {
                    $body .= self::INDENT . str_replace(
                        [
                            '{{ method }}',
                            '{{ modelClass }}',
                            '{{ modelVariable }}',
                        ],
                        [
                            $name,
                            Str::studly($controllerModelName),
                            '$' . Str::camel($controllerModelName),
                        ],
                        in_array($name, ['index', 'create', 'store'])
                            ? "\$this->authorize('{{ method }}', {{ modelClass }}::class);"
                            : "\$this->authorize('{{ method }}', {{ modelVariable }});"
                    ) . PHP_EOL . PHP_EOL;
                }
            }

            foreach ($statements as $statement) {
                if ($statement instanceof SendStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                    if ($statement->type() === SendStatement::TYPE_NOTIFICATION_WITH_FACADE) {
                        $this->addImport($controller, 'Illuminate\\Support\\Facades\\Notification');
                        $this->addImport($controller, config('blueprint.namespace') . '\\Notification\\' . $statement->mail());
                    } elseif ($statement->type() === SendStatement::TYPE_MAIL) {
                        $this->addImport($controller, 'Illuminate\\Support\\Facades\\Mail');
                        $this->addImport($controller, config('blueprint.namespace') . '\\Mail\\' . $statement->mail());
                    }
                } elseif ($statement instanceof ValidateStatement) {
                    $using_validation = true;
                    $class_name = $controller->name() . Str::studly($name) . 'Request';

                    $fqcn = config('blueprint.namespace') . '\\Http\\Requests\\' . ($controller->namespace() ? $controller->namespace() . '\\' : '') . $class_name;

                    $method = str_replace('\Illuminate\Http\Request $request', '\\' . $fqcn . ' $request', $method);
                    $method = str_replace('(Request $request', '(' . $class_name . ' $request', $method);

                    $this->addImport($controller, $fqcn);
                } elseif ($statement instanceof DispatchStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                    $this->addImport($controller, config('blueprint.namespace') . '\\Jobs\\' . $statement->job());
                } elseif ($statement instanceof FireStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                    if (!$statement->isNamedEvent()) {
                        $this->addImport($controller, config('blueprint.namespace') . '\\Events\\' . $statement->event());
                    }
                } elseif ($statement instanceof RenderStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof ResourceStatement) {
                    $fqcn = config('blueprint.namespace') . '\\Http\\Resources\\' . ($controller->namespace() ? $controller->namespace() . '\\' : '') . $statement->name();
                    $this->addImport($controller, $fqcn);
                    $body .= self::INDENT . $statement->output() . PHP_EOL;

                    if ($statement->paginate()) {
                        if (!Str::contains($body, '::all();')) {
                            $queryStatement = new QueryStatement('all', [$statement->reference()]);
                            $body = implode(PHP_EOL, [
                                self::INDENT . $queryStatement->output($statement->reference()),
                                PHP_EOL . $body,
                            ]);

                            $this->addImport($controller, $this->determineModel($controller, $queryStatement->model()));
                        }

                        $body = str_replace('::all();', '::paginate();', $body);
                    }
                } elseif ($statement instanceof RedirectStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof RespondStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof SessionStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof EloquentStatement) {
                    $body .= self::INDENT . $statement->output($controller->prefix(), $name, $using_validation) . PHP_EOL;
                    $this->addImport($controller, $this->determineModel($controller, $statement->reference()));
                } elseif ($statement instanceof QueryStatement) {
                    $body .= self::INDENT . $statement->output($controller->prefix()) . PHP_EOL;
                    $this->addImport($controller, $this->determineModel($controller, $statement->model()));
                }

                $body .= PHP_EOL;
            }

            if (!empty($body)) {
                $method = str_replace('{{ body }}', trim($body), $method);
            }

            if ($statement instanceof RespondStatement && $statement->content()) {
                $method = str_replace('): Response' . PHP_EOL, ')' . PHP_EOL, $method);
            } else {
                $returnType = match (true) {
                    $statement instanceof RenderStatement => 'Illuminate\View\View',
                    $statement instanceof RedirectStatement => 'Illuminate\Http\RedirectResponse',
                    $statement instanceof ResourceStatement => config('blueprint.namespace') . '\\Http\\Resources\\' . ($controller->namespace() ? $controller->namespace() . '\\' : '') . $statement->name(),
                    default => 'Illuminate\Http\Response'
                };

                $method = str_replace('): Response' . PHP_EOL, '): ' . Str::afterLast($returnType, '\\') . PHP_EOL, $method);
                $this->addImport($controller, $returnType);
            }

            $methods .= PHP_EOL . $method;
        }

        return trim($methods);
    }

    private function determineModel(Controller $controller, ?string $reference)
    {
        if (empty($reference) || $reference === 'id') {
            return $this->fullyQualifyModelReference($controller->namespace(), Str::studly(Str::singular($controller->prefix())));
        }

        if (Str::contains($reference, '.')) {
            return $this->fullyQualifyModelReference($controller->namespace(), Str::studly(Str::before($reference, '.')));
        }

        return $this->fullyQualifyModelReference($controller->namespace(), Str::studly($reference));
    }

    private function fullyQualifyModelReference(string $sub_namespace, string $model_name)
    {
        // TODO: get model_name from tree.
        // If not found, assume parallel namespace as controller.
        // Use respond-statement.php as test case.

        /** @var \Blueprint\Models\Model $model */
        $model = $this->tree->modelForContext($model_name);

        if (isset($model)) {
            return $model->fullyQualifiedClassName();
        }

        return config('blueprint.namespace') . '\\' . ($sub_namespace ? $sub_namespace . '\\' : '') . $model_name;
    }
}
