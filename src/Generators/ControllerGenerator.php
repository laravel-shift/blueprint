<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Models\Statements\DispatchStatement;
use Blueprint\Models\Statements\EloquentStatement;
use Blueprint\Models\Statements\FireStatement;
use Blueprint\Models\Statements\QueryStatement;
use Blueprint\Models\Statements\RedirectStatement;
use Blueprint\Models\Statements\RenderStatement;
use Blueprint\Models\Statements\RespondStatement;
use Blueprint\Models\Statements\SendStatement;
use Blueprint\Models\Statements\SessionStatement;
use Blueprint\Models\Statements\ValidateStatement;
use Illuminate\Support\Str;

class ControllerGenerator implements Generator
{
    const INDENT = '        ';

    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    private $imports = [];

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(array $tree): array
    {
        $output = [];

        $stub = $this->files->stub('controller/class.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            $this->addImport($controller, 'Illuminate\\Http\\Request');

            $path = $this->getPath($controller);

            if (!$this->files->exists(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0755, true);
            }

            $this->files->put($path, $this->populateStub($stub, $controller));

            $output['created'][] = $path;
        }

        return $output;
    }

    protected function populateStub(string $stub, Controller $controller)
    {
        $stub = str_replace('DummyNamespace', $controller->fullyQualifiedNamespace(), $stub);
        $stub = str_replace('DummyClass', $controller->className(), $stub);
        $stub = str_replace('// methods...', $this->buildMethods($controller), $stub);
        $stub = str_replace('// imports...', $this->buildImports($controller), $stub);

        return $stub;
    }

    private function buildMethods(Controller $controller)
    {
        $template = $this->files->stub('controller/method.stub');

        $methods = '';

        foreach ($controller->methods() as $name => $statements) {
            $method = str_replace('DummyMethod', $name, $template);

            if (in_array($name, ['edit', 'update', 'show', 'destroy'])) {
                $context = Str::singular($controller->prefix());
                $reference = config('blueprint.namespace') . '\\' . $context;
                $variable = '$' . Str::camel($context);

                // TODO: verify controller prefix references a model
                $search = '     * @return \\Illuminate\\Http\\Response';
                $method = str_replace($search, '     * @param \\' . $reference . ' ' . $variable . PHP_EOL . $search, $method);

                $search = '(Request $request';
                $method = str_replace($search, $search . ', ' . $context . ' ' . $variable, $method);
                $this->addImport($controller, $reference);
            }

            $body = '';
            foreach ($statements as $statement) {
                if ($statement instanceof SendStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                    $this->addImport($controller, 'Illuminate\\Support\\Facades\\Mail');
                    $this->addImport($controller, config('blueprint.namespace') . '\\Mail\\' . $statement->mail());
                } elseif ($statement instanceof ValidateStatement) {
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
                } elseif ($statement instanceof RedirectStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof RespondStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof SessionStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof EloquentStatement) {
                    $body .= self::INDENT . $statement->output($controller->prefix(), $name) . PHP_EOL;
                    $this->addImport($controller, config('blueprint.namespace') . '\\' . ($controller->namespace() ? $controller->namespace() . '\\' : '') . $this->determineModel($controller, $statement->reference()));
                } elseif ($statement instanceof QueryStatement) {
                    $body .= self::INDENT . $statement->output($controller->prefix()) . PHP_EOL;
                    $this->addImport($controller, config('blueprint.namespace') . '\\' . ($controller->namespace() ? $controller->namespace() . '\\' : '') . $this->determineModel($controller, $statement->model()));
                }

                $body .= PHP_EOL;
            }

            if (!empty($body)) {
                $method = str_replace('//', trim($body), $method);
            }

            $methods .= PHP_EOL . $method;
        }

        return trim($methods);
    }

    protected function getPath(Controller $controller)
    {
        $path = str_replace('\\', '/', Blueprint::relativeNamespace($controller->fullyQualifiedClassName()));

        return config('blueprint.app_path') . '/' . $path . '.php';
    }

    private function addImport(Controller $controller, $class)
    {
        $this->imports[$controller->name()][] = $class;
    }

    private function buildImports(Controller $controller)
    {
        $imports = array_unique($this->imports[$controller->name()]);
        sort($imports);

        return implode(PHP_EOL, array_map(function ($class) {
            return 'use ' . $class . ';';
        }, $imports));
    }

    private function determineModel(Controller $controller, ?string $reference)
    {
        if (empty($reference) || $reference === 'id') {
            return Str::studly(Str::singular($controller->prefix()));
        }

        if (Str::contains($reference, '.')) {
            return Str::studly(Str::before($reference, '.'));
        }

        return Str::studly($reference);
    }
}
