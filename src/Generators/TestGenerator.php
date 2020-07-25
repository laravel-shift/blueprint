<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Models\Column;
use Blueprint\Models\Controller;
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
use Shift\Faker\Registry as FakerRegistry;
use Blueprint\Tree;
use Illuminate\Support\Str;

class TestGenerator implements Generator
{
    const TESTS_VIEW = 1;
    const TESTS_REDIRECT = 2;
    const TESTS_SAVE = 4;
    const TESTS_DELETE = 8;
    const TESTS_RESPONDS = 16;

    /** @var \Illuminate\Contracts\Filesystem\Filesystem */
    private $files;

    /** @var Tree */
    private $tree;

    private $imports = [];
    private $stubs = [];
    private $traits = [];

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $output = [];

        $stub = $this->files->stub('test.class.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree->controllers() as $controller) {
            $path = $this->getPath($controller);

            if (! $this->files->exists(dirname($path))) {
                $this->files->makeDirectory(dirname($path), 0755, true);
            }

            $this->files->put($path, $this->populateStub($stub, $controller));

            $output['created'][] = $path;
        }

        return $output;
    }

    public function types(): array
    {
        return ['controllers', 'tests'];
    }

    protected function getPath(Controller $controller)
    {
        $path = str_replace('\\', '/', Blueprint::relativeNamespace($controller->fullyQualifiedClassName()));

        return 'tests/Feature/'.$path.'Test.php';
    }

    protected function populateStub(string $stub, Controller $controller)
    {
        $stub = str_replace('{{ namespace }}', 'Tests\\Feature\\'.Blueprint::relativeNamespace($controller->fullyQualifiedNamespace()), $stub);
        $stub = str_replace('{{ namespacedClass }}', '\\'.$controller->fullyQualifiedClassName(), $stub);
        $stub = str_replace('{{ class }}', $controller->className().'Test', $stub);
        $stub = str_replace('{{ body }}', $this->buildTestCases($controller), $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($controller), $stub);

        return $stub;
    }

    protected function buildTestCases(Controller $controller)
    {
        $template = $this->testCaseStub();
        $test_cases = '';

        foreach ($controller->methods() as $name => $statements) {
            $test_case = $template;
            $setup = [
                'data' => [],
                'mock' => [],
            ];
            $assertions = [
                'sanity' => [],
                'response' => [],
                'generic' => [],
                'mock' => [],
            ];
            $request_data = [];
            $tested_bits = 0;

            $model = $controller->prefix();
            $context = Str::singular($controller->prefix());
            $variable = Str::camel($context);

            $modelNamespace = config('blueprint.models_namespace')
                ? config('blueprint.namespace').'\\'.config('blueprint.models_namespace')
                : config('blueprint.namespace');

            if (in_array($name, ['edit', 'update', 'show', 'destroy'])) {
                $setup['data'][] = sprintf('$%s = factory(%s::class)->create();', $variable, $model);
            }

            foreach ($statements as $statement) {
                if ($statement instanceof SendStatement) {
                    if ($statement->isNotification()) {
                        $this->addImport($controller, 'Illuminate\\Support\\Facades\\Notification');
                        $this->addImport($controller, config('blueprint.namespace').'\\Notification\\'.$statement->mail());

                        $setup['mock'][] = 'Notification::fake();';

                        $assertion = sprintf(
                            'Notification::assertSentTo($%s, %s::class',
                            str_replace('.', '->', $statement->to()),
                            $statement->mail()
                        );

                        if ($statement->data()) {
                            $conditions = [];
                            $variables = [];
                            $assertion .= ', function ($notification)';

                            foreach ($statement->data() as $data) {
                                if (Str::studly(Str::singular($data)) === $context) {
                                    $variables[] .= '$'.$data;
                                    $conditions[] .= sprintf('$notification->%s->is($%s)', $data, $data);
                                } else {
                                    [$model, $property] = explode('.', $data);
                                    $variables[] .= '$'.$model;
                                    $conditions[] .= sprintf('$notification->%s == $%s', $property ?? $model, str_replace('.', '->', $data()));
                                }
                            }

                            if ($variables) {
                                $assertion .= ' use ('.implode(', ', array_unique($variables)).')';
                            }

                            $assertion .= ' {'.PHP_EOL;
                            $assertion .= str_pad(' ', 12);
                            $assertion .= 'return '.implode(' && ', $conditions).';';
                            $assertion .= PHP_EOL.str_pad(' ', 8).'}';
                        }

                        $assertion .= ');';

                        $assertions['mock'][] = $assertion;
                    } else {
                        $this->addImport($controller, 'Illuminate\\Support\\Facades\\Mail');
                        $this->addImport($controller, config('blueprint.namespace').'\\Mail\\'.$statement->mail());

                        $setup['mock'][] = 'Mail::fake();';

                        $assertion = sprintf('Mail::assertSent(%s::class', $statement->mail());

                        if ($statement->data() || $statement->to()) {
                            $conditions = [];
                            $variables = [];
                            $assertion .= ', function ($mail)';

                            if ($statement->to()) {
                                $conditions[] = '$mail->hasTo($'.str_replace('.', '->', $statement->to()).')';
                            }

                            foreach ($statement->data() as $data) {
                                if (Str::studly(Str::singular($data)) === $context) {
                                    $variables[] .= '$'.$data;
                                    $conditions[] .= sprintf('$mail->%s->is($%s)', $data, $data);
                                } else {
                                    [$model, $property] = explode('.', $data);
                                    $variables[] .= '$'.$model;
                                    $conditions[] .= sprintf('$mail->%s == $%s', $property ?? $model, str_replace('.', '->', $data()));
                                }
                            }

                            if ($variables) {
                                $assertion .= ' use ('.implode(', ', array_unique($variables)).')';
                            }

                            $assertion .= ' {'.PHP_EOL;
                            $assertion .= str_pad(' ', 12);
                            $assertion .= 'return '.implode(' && ', $conditions).';';
                            $assertion .= PHP_EOL.str_pad(' ', 8).'}';
                        }

                        $assertion .= ');';

                        $assertions['mock'][] = $assertion;
                    }
                } elseif ($statement instanceof ValidateStatement) {
                    $this->addTestAssertionsTrait($controller);

                    $class = $this->buildFormRequestName($controller, $name);
                    $test_case = $this->buildFormRequestTestCase($controller->fullyQualifiedClassName(), $name, config('blueprint.namespace').'\\Http\\Requests\\'.$class).PHP_EOL.PHP_EOL.$test_case;

                    if ($statement->data()) {
                        $this->addFakerTrait($controller);

                        foreach ($statement->data() as $data) {
                            [$qualifier, $column] = $this->splitField($data);

                            if (is_null($qualifier)) {
                                $qualifier = $context;
                            }

                            $variable_name = $data;

                            /** @var \Blueprint\Models\Model $local_model */
                            $local_model = $this->tree->modelForContext($qualifier);

                            if (! is_null($local_model) && $local_model->hasColumn($column)) {
                                $local_column = $local_model->column($column);

                                $factory = $this->generateReferenceFactory($local_column, $controller, $modelNamespace);

                                if ($factory) {
                                    [$faker, $variable_name] = $factory;
                                } else {
                                    $faker = sprintf('$%s = $this->faker->%s;', $data, FakerRegistry::fakerData($local_column->name()) ?? FakerRegistry::fakerDataType($local_model->column($column)->dataType()));
                                }

                                $setup['data'][] = $faker;
                                $request_data[$data] = '$'.$variable_name;
                            } else {
                                foreach ($local_model->columns() as $local_column) {
                                    if ($local_column->name() === 'id') {
                                        continue;
                                    }

                                    if (in_array('nullable', $local_column->modifiers())) {
                                        continue;
                                    }

                                    $factory = $this->generateReferenceFactory($local_column, $controller, $modelNamespace);
                                    if ($factory) {
                                        [$faker, $variable_name] = $factory;
                                    } else {
                                        $faker = sprintf('$%s = $this->faker->%s;', $local_column->name(), FakerRegistry::fakerData($local_column->name()) ?? FakerRegistry::fakerDataType($local_column->dataType()));
                                        $variable_name = $local_column->name();
                                    }

                                    $setup['data'][] = $faker;
                                    $request_data[$local_column->name()] = '$'.$variable_name;
                                }
                            }
                        }
                    }
                } elseif ($statement instanceof DispatchStatement) {
                    $this->addImport($controller, 'Illuminate\\Support\\Facades\\Queue');
                    $this->addImport($controller, config('blueprint.namespace').'\\Jobs\\'.$statement->job());

                    $setup['mock'][] = 'Queue::fake();';

                    $assertion = sprintf('Queue::assertPushed(%s::class', $statement->job());

                    if ($statement->data()) {
                        $conditions = [];
                        $variables = [];
                        $assertion .= ', function ($job)';

                        foreach ($statement->data() as $data) {
                            if (Str::studly(Str::singular($data)) === $context) {
                                $variables[] .= '$'.$data;
                                $conditions[] .= sprintf('$job->%s->is($%s)', $data, $data);
                            } else {
                                [$model, $property] = explode('.', $data);
                                $variables[] .= '$'.$model;
                                $conditions[] .= sprintf('$job->%s == $%s', $property ?? $model, str_replace('.', '->', $data()));
                            }
                        }

                        if ($variables) {
                            $assertion .= ' use ('.implode(', ', array_unique($variables)).')';
                        }

                        $assertion .= ' {'.PHP_EOL;
                        $assertion .= str_pad(' ', 12);
                        $assertion .= 'return '.implode(' && ', $conditions).';';
                        $assertion .= PHP_EOL.str_pad(' ', 8).'}';
                    }

                    $assertion .= ');';

                    $assertions['mock'][] = $assertion;
                } elseif ($statement instanceof FireStatement) {
                    $this->addImport($controller, 'Illuminate\\Support\\Facades\\Event');

                    $setup['mock'][] = 'Event::fake();';

                    $assertion = 'Event::assertDispatched(';

                    if ($statement->isNamedEvent()) {
                        $assertion .= $statement->event();
                    } else {
                        $this->addImport($controller, config('blueprint.namespace').'\\Events\\'.$statement->event());
                        $assertion .= $statement->event().'::class';
                    }

                    if ($statement->data()) {
                        $conditions = [];
                        $variables = [];
                        $assertion .= ', function ($event)';

                        foreach ($statement->data() as $data) {
                            if (Str::studly(Str::singular($data)) === $context) {
                                $variables[] .= '$'.$data;
                                $conditions[] .= sprintf('$event->%s->is($%s)', $data, $data);
                            } else {
                                [$model, $property] = explode('.', $data);
                                $variables[] .= '$'.$model;
                                $conditions[] .= sprintf('$event->%s == $%s', $property ?? $model, str_replace('.', '->', $data()));
                            }
                        }

                        if ($variables) {
                            $assertion .= ' use ('.implode(', ', array_unique($variables)).')';
                        }

                        $assertion .= ' {'.PHP_EOL;
                        $assertion .= str_pad(' ', 12);
                        $assertion .= 'return '.implode(' && ', $conditions).';';
                        $assertion .= PHP_EOL.str_pad(' ', 8).'}';
                    }

                    $assertion .= ');';

                    $assertions['mock'][] = $assertion;
                } elseif ($statement instanceof RenderStatement) {
                    $tested_bits |= self::TESTS_VIEW;

                    $view_assertions = [];
                    $view_assertions[] = '$response->assertOk();';
                    $view_assertions[] = sprintf('$response->assertViewIs(\'%s\');', $statement->view());

                    foreach ($statement->data() as $data) {
                        // TODO: if data references locally scoped var, strengthen assertion...
                        $view_assertions[] = sprintf('$response->assertViewHas(\'%s\');', $data);
                    }

                    array_unshift($assertions['response'], ...$view_assertions);
                } elseif ($statement instanceof RedirectStatement) {
                    $tested_bits |= self::TESTS_REDIRECT;

                    $assertion = sprintf('$response->assertRedirect(route(\'%s\'', $statement->route());

                    if ($statement->data()) {
                        $parameters = array_map(function ($parameter) {
                            return '$'.$parameter;
                        }, $statement->data());

                        $assertion .= ', ['.implode(', ', $parameters).']';
                    } elseif (Str::contains($statement->route(), '.')) {
                        [$model, $action] = explode('.', $statement->route());
                        if (in_array($action, ['edit', 'update', 'show', 'destroy'])) {
                            $assertion .= sprintf(", ['%s' => $%s]", $model, $model);
                        }
                    }

                    $assertion .= '));';

                    array_unshift($assertions['response'], $assertion);
                } elseif ($statement instanceof ResourceStatement) {
                    if ($name === 'store') {
                        $assertions['response'][] = '$response->assertCreated();';
                    } else {
                        $assertions['response'][] = '$response->assertOk();';
                    }

                    $assertions['response'][] = '$response->assertJsonStructure([]);';
                } elseif ($statement instanceof RespondStatement) {
                    $tested_bits |= self::TESTS_RESPONDS;

                    if ($statement->content()) {
                        array_unshift($assertions['response'], '$response->assertJson($'.$statement->content().');');
                    }

                    if ($statement->status() === 200) {
                        array_unshift($assertions['response'], '$response->assertOk();');
                    } elseif ($statement->status() === 204) {
                        array_unshift($assertions['response'], '$response->assertNoContent();');
                    } else {
                        array_unshift($assertions['response'], '$response->assertNoContent('.$statement->status().');');
                    }
                } elseif ($statement instanceof SessionStatement) {
                    $assertions['response'][] = sprintf('$response->assertSessionHas(\'%s\', %s);', $statement->reference(), '$'.str_replace('.', '->', $statement->reference()));
                } elseif ($statement instanceof EloquentStatement) {
                    $this->addRefreshDatabaseTrait($controller);

                    $model = $this->determineModel($controller->prefix(), $statement->reference());
                    $this->addImport($controller, $modelNamespace.'\\'.$model);

                    if ($statement->operation() === 'save') {
                        $tested_bits |= self::TESTS_SAVE;

                        if ($request_data) {
                            $indent = str_pad(' ', 12);
                            $plural = Str::plural($variable);
                            $assertion = sprintf('$%s = %s::query()', $plural, $model);
                            foreach ($request_data as $key => $datum) {
                                $assertion .= PHP_EOL.sprintf('%s->where(\'%s\', %s)', $indent, $key, $datum);
                            }
                            $assertion .= PHP_EOL.$indent.'->get();';

                            $assertions['sanity'][] = $assertion;
                            $assertions['sanity'][] = '$this->assertCount(1, $'.$plural.');';
                            $assertions['sanity'][] = sprintf('$%s = $%s->first();', $variable, $plural);
                        } else {
                            $assertions['generic'][] = '$this->assertDatabaseHas('.Str::camel(Str::plural($model)).', [ /* ... */ ]);';
                        }
                    } elseif ($statement->operation() === 'find') {
                        $setup['data'][] = sprintf('$%s = factory(%s::class)->create();', $variable, $model);
                    } elseif ($statement->operation() === 'delete') {
                        $tested_bits |= self::TESTS_DELETE;
                        $setup['data'][] = sprintf('$%s = factory(%s::class)->create();', $variable, $model);
                        $assertions['generic'][] = sprintf('$this->assertDeleted($%s);', $variable);
                    } elseif ($statement->operation() === 'update') {
                        $assertions['sanity'][] = sprintf('$%s->refresh();', $variable);

                        if ($request_data) {
                            foreach ($request_data as $key => $datum) {
                                $assertions['generic'][] = sprintf('$this->assertEquals(%s, $%s->%s);', $datum, $variable, $key);
                            }
                        }
                    }
                } elseif ($statement instanceof QueryStatement) {
                    $this->addRefreshDatabaseTrait($controller);

                    $setup['data'][] = sprintf('$%s = factory(%s::class, 3)->create();', Str::plural($variable), $model);

                    $this->addImport($controller, $modelNamespace.'\\'.$this->determineModel($controller->prefix(), $statement->model()));
                }
            }

            $call = sprintf('$response = $this->%s(route(\'%s.%s\'', $this->httpMethodForAction($name), Str::kebab($context), $name);

            if (in_array($name, ['edit', 'update', 'show', 'destroy'])) {
                $call .= ', $'.Str::camel($context);
            }
            $call .= ')';

            if ($request_data) {
                $call .= ', [';
                $call .= PHP_EOL;
                foreach ($request_data as $key => $datum) {
                    $call .= str_pad(' ', 12);
                    $call .= sprintf('\'%s\' => %s,', $key, $datum);
                    $call .= PHP_EOL;
                }

                $call .= str_pad(' ', 8).']';
            }
            $call .= ');';

            $body = implode(PHP_EOL.PHP_EOL, array_map([$this, 'buildLines'], $this->uniqueSetupLines($setup)));
            $body .= PHP_EOL.PHP_EOL;
            $body .= str_pad(' ', 8).$call;
            $body .= PHP_EOL.PHP_EOL;
            $body .= implode(PHP_EOL.PHP_EOL, array_map([$this, 'buildLines'], array_filter($assertions)));

            $test_case = str_replace('{{ method }}', $this->buildTestCaseName($name, $tested_bits), $test_case);
            $test_case = str_replace('{{ body }}', trim($body), $test_case);

            $test_cases .= PHP_EOL.$test_case.PHP_EOL;
        }

        return trim($this->buildTraits($controller).PHP_EOL.$test_cases);
    }

    protected function addTrait(Controller $controller, $trait)
    {
        $this->traits[$controller->name()][] = $trait;
    }

    protected function addImport(Controller $controller, $class)
    {
        $this->imports[$controller->name()][] = $class;
    }

    protected function buildImports(Controller $controller)
    {
        $this->addImport($controller, 'Tests\\TestCase');

        $imports = array_unique($this->imports[$controller->name()]);
        sort($imports);

        return implode(PHP_EOL, array_map(function ($class) {
            return 'use '.$class.';';
        }, $imports));
    }

    private function buildTraits(Controller $controller)
    {
        if (empty($this->traits[$controller->name()])) {
            return '';
        }

        $traits = array_unique($this->traits[$controller->name()]);
        sort($traits);

        return 'use '.implode(', ', $traits).';';
    }

    private function testCaseStub()
    {
        if (empty($this->stubs['test-case'])) {
            $this->stubs['test-case'] = $this->files->stub('test.case.stub');
        }

        return $this->stubs['test-case'];
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

    private function buildFormRequestName(Controller $controller, string $name)
    {
        if (empty($controller->namespace())) {
            return $controller->name().Str::studly($name).'Request';
        }

        return $controller->namespace().'\\'.$controller->name().Str::studly($name).'Request';
    }

    private function buildFormRequestTestCase(string $controller, string $action, string $form_request)
    {
        return <<< END
    /**
     * @test
     */
    public function ${action}_uses_form_request_validation()
    {
        \$this->assertActionUsesFormRequest(
            \\${controller}::class,
            '${action}',
            \\${form_request}::class
        );
    }
END;
    }

    private function addFakerTrait(Controller $controller)
    {
        $this->addImport($controller, 'Illuminate\\Foundation\\Testing\\WithFaker');
        $this->addTrait($controller, 'WithFaker');
    }

    private function addTestAssertionsTrait(Controller $controller)
    {
        $this->addImport($controller, 'JMac\\Testing\\Traits\AdditionalAssertions');
        $this->addTrait($controller, 'AdditionalAssertions');
    }

    private function addRefreshDatabaseTrait(Controller $controller)
    {
        $this->addImport($controller, 'Illuminate\\Foundation\\Testing\\RefreshDatabase');
        $this->addTrait($controller, 'RefreshDatabase');
    }

    private function httpMethodForAction($action)
    {
        switch ($action) {
            case 'store':
                return 'post';
            case 'update':
                return 'put';
            case 'destroy':
                return 'delete';
            default:
                return 'get';
        }
    }

    private function buildTestCaseName(string $name, int $tested_bits)
    {
        $verifications = [];

        if ($tested_bits & self::TESTS_SAVE) {
            $verifications[] = 'saves';
        }

        if ($tested_bits & self::TESTS_DELETE) {
            $verifications[] = 'deletes';
        }

        if ($tested_bits & self::TESTS_VIEW) {
            $verifications[] = 'displays_view';
        }

        if ($tested_bits & self::TESTS_REDIRECT) {
            $verifications[] = 'redirects';
        }

        if ($tested_bits & self::TESTS_RESPONDS) {
            $verifications[] = 'responds_with';
        }

        if (empty($verifications)) {
            return $name.'_behaves_as_expected';
        }

        $final_verification = array_pop($verifications);

        if (empty($verifications)) {
            return $name.'_'.$final_verification;
        }

        return $name.'_'.implode('_', $verifications).'_and_'.$final_verification;
    }

    private function buildLines($lines)
    {
        return str_pad(' ', 8).implode(PHP_EOL.str_pad(' ', 8), $lines);
    }

    private function splitField($field)
    {
        if (Str::contains($field, '.')) {
            return explode('.', $field, 2);
        }

        return [null, $field];
    }

    private function uniqueSetupLines(array $setup)
    {
        return collect($setup)->filter()
            ->map(function ($lines) {
                return array_unique($lines);
            })
            ->toArray();
    }

    private function generateReferenceFactory(Column $local_column, Controller $controller, string $modelNamespace)
    {
        if (!in_array($local_column->dataType(), ['id', 'uuid']) && !($local_column->attributes() && Str::endsWith($local_column->name(), '_id'))) {
            return null;
        }

        $reference = Str::beforeLast($local_column->name(), '_id');
        $variable_name = $reference.'->id';

        if ($local_column->attributes()) {
            $reference = $local_column->attributes()[0];
        }

        $faker = sprintf('$%s = factory(%s::class)->create();', Str::beforeLast($local_column->name(), '_id'), Str::studly($reference));

        $this->addImport($controller, $modelNamespace.'\\'.Str::studly($reference));

        return [$faker, $variable_name];
    }
}
