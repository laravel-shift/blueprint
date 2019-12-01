<?php

namespace Blueprint\Generators;

use Blueprint\Contracts\Generator;
use Blueprint\Models\Controller;
use Blueprint\Models\Statements\DispatchStatement;
use Blueprint\Models\Statements\EloquentStatement;
use Blueprint\Models\Statements\FireStatement;
use Blueprint\Models\Statements\QueryStatement;
use Blueprint\Models\Statements\RedirectStatement;
use Blueprint\Models\Statements\RenderStatement;
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

        $stub = $this->files->get(STUBS_PATH . '/controller/class.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree['controllers'] as $controller) {
            $this->addImport($controller, 'Illuminate\\Http\\Request');
            $path = $this->getPath($controller);
            $this->files->put(
                $path,
                $this->populateStub($stub, $controller)
            );

            $output['created'][] = $path;
        }

        return $output;
    }

    protected function populateStub(string $stub, Controller $controller)
    {
        $stub = str_replace('DummyNamespace', 'App\\Http\\Controllers', $stub);
        $stub = str_replace('DummyClass', $controller->className(), $stub);
        $stub = str_replace('// methods...', $this->buildMethods($controller), $stub);
        $stub = str_replace('// imports...', $this->buildImports($controller), $stub);

        return $stub;
    }

    private function buildMethods(Controller $controller)
    {
        $template = $this->methodStub();

        $methods = '';

        foreach ($controller->methods() as $name => $statements) {
            $method = str_replace('DummyMethod', $name, $template);

            if (in_array($name, ['edit', 'update', 'show', 'destroy'])) {
                $context = Str::singular($controller->prefix());
                $reference = 'App\\' . $context;
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
                    $this->addImport($controller, 'App\\Mail\\' . $statement->mail());
                } elseif ($statement instanceof ValidateStatement) {
                    $class = $controller->name() . Str::studly($name) . 'Request';

                    $method = str_replace('\Illuminate\Http\Request $request', '\\App\\Http\\Requests\\' . $class . ' $request', $method);
                    $method = str_replace('(Request $request', '(' . $class . ' $request', $method);

                    $this->addImport($controller, 'App\\Http\\Requests\\' . $class);
                } elseif ($statement instanceof DispatchStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                    $this->addImport($controller, 'App\\Jobs\\' . $statement->job());
                } elseif ($statement instanceof FireStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                    if (!$statement->isNamedEvent()) {
                        $this->addImport($controller, 'App\\Events\\' . $statement->event());
                    }
                } elseif ($statement instanceof RenderStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof RedirectStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof SessionStatement) {
                    $body .= self::INDENT . $statement->output() . PHP_EOL;
                } elseif ($statement instanceof EloquentStatement) {
                    $body .= self::INDENT . $statement->output($controller->prefix(), $name) . PHP_EOL;
                    $this->addImport($controller, 'App\\' . $this->determineModel($controller->prefix(), $statement->reference()));
                } elseif ($statement instanceof QueryStatement) {
                    $body .= self::INDENT . $statement->output($controller->prefix()) . PHP_EOL;
                    $this->addImport($controller, 'App\\' . $this->determineModel($controller->prefix(), $statement->model()));
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
        return 'app/Http/Controllers/' . $controller->className() . '.php';
    }

    private function methodStub()
    {
        static $stub = '';

        if (empty($stub)) {
            $stub = $this->files->get(STUBS_PATH . '/controller/method.stub');
        }

        return $stub;
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

    private function determineModel(string $prefix, ?string $reference)
    {
        if (empty($reference) || $reference === 'id') {
            return Str::studly(Str::singular($prefix));
        }

        if (Str::contains($reference, '.')) {
            return Str::studly(Str::before($reference, '.'));
        }

        return Str::studly($reference);
    }
}
