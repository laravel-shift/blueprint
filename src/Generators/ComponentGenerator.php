<?php

namespace Blueprint\Generators;

use Blueprint\Concerns\HandlesImports;
use Blueprint\Concerns\HandlesTraits;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Component;
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

class ComponentGenerator extends AbstractClassGenerator implements Generator
{
    use HandlesImports, HandlesTraits;

    protected array $types = ['components'];

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $stub = $this->filesystem->stub('livewire.class.stub');

        foreach ($tree->components() as $component) {
            $this->addImport($component, 'Livewire\\Component');
            $path = $this->getPath($component);

            $this->create($path, $this->populateStub($stub, $component));
            $this->create($this->viewPath($component), $this->filesystem->stub('livewire.view.stub'));
        }

        return $this->output;
    }

    protected function populateStub(string $stub, Component $component): string
    {
        $stub = str_replace('{{ namespace }}', $component->fullyQualifiedNamespace(), $stub);
        $stub = str_replace('{{ class }}', $component->className(), $stub);
        $stub = str_replace('{{ body }}', $this->buildBody($component), $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($component), $stub);

        return $stub;
    }

    protected function buildMethods(Component $component): string
    {
        $template = $this->filesystem->stub('livewire.method.stub');

        $methods = '';

        if ($component->properties()) {
            $methods .= PHP_EOL . $this->buildMountMethod($component->properties(), str_replace('{{ method }}', 'mount', $template));
        }

        foreach ($component->methods() as $name => $statements) {
            $method = str_replace('{{ method }}', $name, $template);

            $body = '';
            $using_validation = false;

            foreach ($statements as $statement) {
                if ($statement instanceof SendStatement) {
                    $body .= self::INDENT . $statement->withProperties($component->properties())->output() . PHP_EOL;
                    if ($statement->type() === SendStatement::TYPE_NOTIFICATION_WITH_FACADE) {
                        $this->addImport($component, 'Illuminate\\Support\\Facades\\Notification');
                        $this->addImport($component, config('blueprint.namespace') . '\\Notification\\' . $statement->mail());
                    } elseif ($statement->type() === SendStatement::TYPE_MAIL) {
                        $this->addImport($component, 'Illuminate\\Support\\Facades\\Mail');
                        $this->addImport($component, config('blueprint.namespace') . '\\Mail\\' . $statement->mail());
                    }
                } elseif ($statement instanceof ValidateStatement) {
                    $using_validation = true;
                    $class_name = $component->name() . Str::studly($name) . 'Request';

                    $fqcn = config('blueprint.namespace') . '\\Http\\Requests\\' . ($component->namespace() ? $component->namespace() . '\\' : '') . $class_name;

                    $method = str_replace('\Illuminate\Http\Request $request', '\\' . $fqcn . ' $request', $method);
                    $method = str_replace('(Request $request', '(' . $class_name . ' $request', $method);

                    $this->addImport($component, $fqcn);
                } elseif ($statement instanceof DispatchStatement) {
                    $body .= self::INDENT . $statement->withProperties($component->properties())->output() . PHP_EOL;
                    $this->addImport($component, config('blueprint.namespace') . '\\Jobs\\' . $statement->job());
                } elseif ($statement instanceof FireStatement) {
                    $body .= self::INDENT . $statement->withProperties($component->properties())->output() . PHP_EOL;
                    if (!$statement->isNamedEvent()) {
                        $this->addImport($component, config('blueprint.namespace') . '\\Events\\' . $statement->event());
                    }
                } elseif ($statement instanceof RenderStatement) {
                    $body .= self::INDENT . $statement->withProperties($component->properties())->output() . PHP_EOL;
                } elseif ($statement instanceof ResourceStatement) {
                    $fqcn = config('blueprint.namespace') . '\\Http\\Resources\\' . ($component->namespace() ? $component->namespace() . '\\' : '') . $statement->name();
                    $this->addImport($component, $fqcn);
                    $body .= self::INDENT . $statement->output($component->properties()) . PHP_EOL;

                    if ($statement->paginate()) {
                        if (!Str::contains($body, '::all();')) {
                            $queryStatement = new QueryStatement('all', [$statement->reference()]);
                            $body = implode(PHP_EOL, [
                                self::INDENT . $queryStatement->output($statement->reference()),
                                PHP_EOL . $body,
                            ]);

                            $this->addImport($component, $this->determineModel($component, $queryStatement->model()));
                        }

                        $body = str_replace('::all();', '::paginate();', $body);
                    }
                } elseif ($statement instanceof RedirectStatement) {
                    $body .= self::INDENT . $statement->withProperties($component->properties())->output() . PHP_EOL;
                } elseif ($statement instanceof RespondStatement) {
                    $body .= self::INDENT . $statement->output($component->properties()) . PHP_EOL;
                } elseif ($statement instanceof SessionStatement) {
                    $body .= self::INDENT . $statement->output($component->properties(), true) . PHP_EOL;
                } elseif ($statement instanceof EloquentStatement) {
                    dump($statement->operation());
                    $body .= self::INDENT . $statement->output($component->prefix(), $name, $using_validation) . PHP_EOL;
                    $this->addImport($component, $this->determineModel($component, $statement->reference()));
                } elseif ($statement instanceof QueryStatement) {
                    $body .= self::INDENT . $statement->output($component->prefix()) . PHP_EOL;
                    $this->addImport($component, $this->determineModel($component, $statement->model()));
                }

                $body .= PHP_EOL;
            }

            if (!empty($body)) {
                $method = str_replace('{{ body }}', trim($body), $method);
            }

            $returnType = match (true) {
                $statement instanceof RenderStatement => 'Illuminate\View\View',
                $statement instanceof RedirectStatement => 'Illuminate\Http\RedirectResponse',
                $statement instanceof ResourceStatement => config('blueprint.namespace') . '\\Http\\Resources\\' . ($component->namespace() ? $component->namespace() . '\\' : '') . $statement->name(),
                default => null
            };

            if (!is_null($returnType)) {
                $method = str_replace($name . '()' . PHP_EOL, $name . '(): ' . Str::afterLast($returnType, '\\') . PHP_EOL, $method);
                $this->addImport($component, $returnType);
            }

            $methods .= PHP_EOL . $method;
        }

        return $methods;
    }

    private function buildBody(Component $component): string
    {
        $properties = $this->buildProperties($component);
        $methods = $this->buildMethods($component);

        if (empty($properties)) {
            return trim($methods);
        }

        return trim($properties) . PHP_EOL . rtrim($methods);
    }

    private function buildMountMethod(array $properties, string $template): string
    {
        ksort($properties);

        $signature = sprintf(
            'mount(%s): void',
            implode(', ', array_map(fn ($name) => '$' . $name, $properties))
        );
        $output = str_replace('mount()', $signature, $template);

        $body = implode(PHP_EOL, array_map(fn ($name) => '        $this->' . $name . ' = $' . $name . ';', $properties));
        $output = str_replace('{{ body }}', trim($body), $output);

        return $output;
    }

    private function buildProperties(Component $component): string
    {
        $properties = $component->properties();
        if (empty($properties)) {
            return '';
        }

        ksort($properties);

        $output = '';
        foreach ($properties as $property) {
            $output .= <<<PHP
    #[Locked]
    public \${$property};


PHP;
        }

        $this->addImport($component, 'Livewire\\Attributes\\Locked');

        return $output;
    }

    private function viewPath(Component $component): string
    {
        $relative = Str::of($component->namespace() . '\\' . $component->className())
            ->replace('\\', DIRECTORY_SEPARATOR)
            ->ltrim(DIRECTORY_SEPARATOR)
            ->snake('-')
            ->value();

        return 'resources/views/livewire/' . $relative . '.blade.php';
    }
}
