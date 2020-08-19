<?php

namespace Blueprint\Models;

use Blueprint\Blueprint;
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
use Shift\Faker\Registry as FakerRegistry;

class TestCase
{
    const TESTS_VIEW = 1;
    const TESTS_REDIRECT = 2;
    const TESTS_SAVE = 4;
    const TESTS_DELETE = 8;
    const TESTS_RESPONDS = 16;

    /** @var Controller */
    private $controller;

    /** @var Tree */
    private $tree;

    private $name;
    private $statements;

    public $imports = [];
    public $traits = [];

    public function __construct(Controller $controller, Tree $tree, $name, $statements)
    {
        $this->controller = $controller;
        $this->tree = $tree;

        $this->name = $name;
        $this->statements = $statements;

        $this->model = $this->controller->prefix();
        $this->context = Str::singular($this->model);
        $this->variable = Str::camel($this->context);
        $this->modelNamespace = Blueprint::modelNamespace();
    }

    public $setup = [
        'data' => [],
        'mock' => [],
    ];
    public $assertions = [
        'sanity' => [],
        'response' => [],
        'generic' => [],
        'mock' => [],
    ];
    public $request_data = [];
    public $tested_bits = 0;

    public function build($test_case)
    {
        if ($this->methodWithModel($this->name)) {
            $this->setup['data'][] = sprintf('$%s = factory(%s::class)->create();', $this->variable, $this->model);
        }

        foreach ($this->statements as $statement) {
            if ($statement instanceof SendStatement) {
                if ($statement->isNotification()) {
                    $this->buildNotificationSendStatement($statement);
                } else {
                    $this->buildEmailSendStatement($statement);
                }
            } elseif ($statement instanceof ValidateStatement) {
                $class = $this->buildFormRequestName($this->controller, $this->name);
                $test_case = $this->buildFormRequestTestCase($this->controller->fullyQualifiedClassName(), $this->name, config('blueprint.namespace') . '\\Http\\Requests\\' . $class) . PHP_EOL . PHP_EOL . $test_case;

                $this->buildValidateStatement($statement);
            } elseif ($statement instanceof DispatchStatement) {
                $this->buildDispatchStatement($statement);
            } elseif ($statement instanceof FireStatement) {
                $this->buildFireStatement($statement);
            } elseif ($statement instanceof RenderStatement) {
                $this->buildRenderStatement($statement);
            } elseif ($statement instanceof RedirectStatement) {
                $this->buildRedirectStatement($statement);
            } elseif ($statement instanceof ResourceStatement) {
                $this->buildResourceStatement($statement);
            } elseif ($statement instanceof RespondStatement) {
                $this->buildRespondStatement($statement);
            } elseif ($statement instanceof SessionStatement) {
                $this->buildSessionStatement($statement);
            } elseif ($statement instanceof EloquentStatement) {
                $this->buildEloquentStatement($statement);
            } elseif ($statement instanceof QueryStatement) {
                $this->buildQueryStatement($statement);
            }
        }

        $call = $this->buildCall();

        $body = implode(PHP_EOL . PHP_EOL, array_map([$this, 'buildLines'], $this->uniqueSetupLines($this->setup)));
        $body .= PHP_EOL . PHP_EOL;
        $body .= str_pad(' ', 8) . $call;
        $body .= PHP_EOL . PHP_EOL;
        $body .= implode(PHP_EOL . PHP_EOL, array_map([$this, 'buildLines'], array_filter($this->assertions)));

        $test_case = str_replace('{{ method }}', $this->buildTestCaseName($this->name, $this->tested_bits), $test_case);
        $test_case = str_replace('{{ body }}', trim($body), $test_case);

        return PHP_EOL . $test_case . PHP_EOL;
    }

    protected function methodWithModel(string $name)
    {
        return in_array($name, ['edit', 'update', 'show', 'destroy']);
    }

    protected function addImport(Controller $controller, $class)
    {
        $this->imports[$controller->name()][] = $class;
    }

    private function buildNotificationSendStatement(SendStatement $statement)
    {
        $this->addImport($this->controller, 'Illuminate\\Support\\Facades\\Notification');
        $this->addImport($this->controller, config('blueprint.namespace') . '\\Notification\\' . $statement->mail());

        $this->setup['mock'][] = 'Notification::fake();';

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
                if (Str::studly(Str::singular($data)) === $this->context) {
                    $variables[] .= '$' . $data;
                    $conditions[] .= sprintf('$notification->%s->is($%s)', $data, $data);
                } else {
                    [$model, $property] = explode('.', $data);
                    $variables[] .= '$' . $model;
                    $conditions[] .= sprintf('$notification->%s == $%s', $property ?? $model, str_replace('.', '->', $data()));
                }
            }

            if ($variables) {
                $assertion .= ' use (' . implode(', ', array_unique($variables)) . ')';
            }

            $assertion .= ' {' . PHP_EOL;
            $assertion .= str_pad(' ', 12);
            $assertion .= 'return ' . implode(' && ', $conditions) . ';';
            $assertion .= PHP_EOL . str_pad(' ', 8) . '}';
        }

        $assertion .= ');';

        $this->assertions['mock'][] = $assertion;
    }

    private function buildEmailSendStatement(SendStatement $statement)
    {
        $this->addImport($this->controller, 'Illuminate\\Support\\Facades\\Mail');
        $this->addImport($this->controller, config('blueprint.namespace') . '\\Mail\\' . $statement->mail());

        $this->setup['mock'][] = 'Mail::fake();';

        $assertion = sprintf('Mail::assertSent(%s::class', $statement->mail());

        if ($statement->data() || $statement->to()) {
            $conditions = [];
            $variables = [];
            $assertion .= ', function ($mail)';

            if ($statement->to()) {
                $conditions[] = '$mail->hasTo($' . str_replace('.', '->', $statement->to()) . ')';
            }

            foreach ($statement->data() as $data) {
                if (Str::studly(Str::singular($data)) === $this->context) {
                    $variables[] .= '$' . $data;
                    $conditions[] .= sprintf('$mail->%s->is($%s)', $data, $data);
                } else {
                    [$model, $property] = explode('.', $data);
                    $variables[] .= '$' . $model;
                    $conditions[] .= sprintf('$mail->%s == $%s', $property ?? $model, str_replace('.', '->', $data()));
                }
            }

            if ($variables) {
                $assertion .= ' use (' . implode(', ', array_unique($variables)) . ')';
            }

            $assertion .= ' {' . PHP_EOL;
            $assertion .= str_pad(' ', 12);
            $assertion .= 'return ' . implode(' && ', $conditions) . ';';
            $assertion .= PHP_EOL . str_pad(' ', 8) . '}';
        }

        $assertion .= ');';

        $this->assertions['mock'][] = $assertion;
    }

    private function addTestAssertionsTrait(Controller $controller)
    {
        $this->addImport($controller, 'JMac\\Testing\\Traits\AdditionalAssertions');
        $this->addTrait($controller, 'AdditionalAssertions');
    }

    protected function addTrait(Controller $controller, $trait)
    {
        $this->traits[$controller->name()][] = $trait;
    }

    private function addFakerTrait(Controller $controller)
    {
        $this->addImport($controller, 'Illuminate\\Foundation\\Testing\\WithFaker');
        $this->addTrait($controller, 'WithFaker');
    }

    private function addRefreshDatabaseTrait(Controller $controller)
    {
        $this->addImport($controller, 'Illuminate\\Foundation\\Testing\\RefreshDatabase');
        $this->addTrait($controller, 'RefreshDatabase');
    }

    private function buildFormRequestName(Controller $controller, string $name)
    {
        if (empty($controller->namespace())) {
            return $controller->name() . Str::studly($name) . 'Request';
        }

        return $controller->namespace() . '\\' . $controller->name() . Str::studly($name) . 'Request';
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

    private function splitField($field)
    {
        if (Str::contains($field, '.')) {
            return explode('.', $field, 2);
        }

        return [null, $field];
    }

    private function generateReferenceFactory(Column $local_column, Controller $controller, string $modelNamespace)
    {
        if (!in_array($local_column->dataType(), ['id', 'uuid']) && !($local_column->attributes() && Str::endsWith($local_column->name(), '_id'))) {
            return null;
        }

        $reference = Str::beforeLast($local_column->name(), '_id');
        $variable_name = $reference . '->id';

        if ($local_column->attributes()) {
            $reference = $local_column->attributes()[0];
        }

        $faker = sprintf('$%s = factory(%s::class)->create();', Str::beforeLast($local_column->name(), '_id'), Str::studly($reference));

        $this->addImport($controller, $modelNamespace . '\\' . Str::studly($reference));

        return [$faker, $variable_name];
    }

    private function buildValidateStatement(ValidateStatement $statement)
    {
        $this->addTestAssertionsTrait($this->controller);

        if ($statement->data()) {
            $this->addFakerTrait($this->controller);

            foreach ($statement->data() as $data) {
                [$qualifier, $column] = $this->splitField($data);

                if (is_null($qualifier)) {
                    $qualifier = $this->context;
                }

                $variable_name = $data;

                /** @var \Blueprint\Models\Model $local_model */
                $local_model = $this->tree->modelForContext($qualifier);

                if (!is_null($local_model) && $local_model->hasColumn($column)) {
                    $local_column = $local_model->column($column);

                    $factory = $this->generateReferenceFactory($local_column, $this->controller, $this->modelNamespace);

                    if ($factory) {
                        [$faker, $variable_name] = $factory;
                    } else {
                        $faker = sprintf('$%s = $this->faker->%s;', $data, FakerRegistry::fakerData($local_column->name()) ?? FakerRegistry::fakerDataType($local_model->column($column)->dataType()));
                    }

                    $this->setup['data'][] = $faker;
                    $this->request_data[$data] = '$' . $variable_name;
                } else {
                    foreach ($local_model->columns() as $local_column) {
                        if ($local_column->name() === 'id') {
                            continue;
                        }

                        if (in_array('nullable', $local_column->modifiers())) {
                            continue;
                        }

                        $factory = $this->generateReferenceFactory($local_column, $this->controller, $this->modelNamespace);
                        if ($factory) {
                            [$faker, $variable_name] = $factory;
                        } else {
                            $faker = sprintf('$%s = $this->faker->%s;', $local_column->name(), FakerRegistry::fakerData($local_column->name()) ?? FakerRegistry::fakerDataType($local_column->dataType()));
                            $variable_name = $local_column->name();
                        }

                        $this->setup['data'][] = $faker;
                        $this->request_data[$local_column->name()] = '$' . $variable_name;
                    }
                }
            }
        }
    }

    private function buildDispatchStatement(DispatchStatement $statement)
    {
        $this->addImport($this->controller, 'Illuminate\\Support\\Facades\\Queue');
        $this->addImport($this->controller, config('blueprint.namespace') . '\\Jobs\\' . $statement->job());

        $this->setup['mock'][] = 'Queue::fake();';

        $assertion = sprintf('Queue::assertPushed(%s::class', $statement->job());

        if ($statement->data()) {
            $conditions = [];
            $variables = [];
            $assertion .= ', function ($job)';

            foreach ($statement->data() as $data) {
                if (Str::studly(Str::singular($data)) === $this->context) {
                    $variables[] .= '$' . $data;
                    $conditions[] .= sprintf('$job->%s->is($%s)', $data, $data);
                } else {
                    [$model, $property] = explode('.', $data);
                    $variables[] .= '$' . $model;
                    $conditions[] .= sprintf('$job->%s == $%s', $property ?? $model, str_replace('.', '->', $data()));
                }
            }

            if ($variables) {
                $assertion .= ' use (' . implode(', ', array_unique($variables)) . ')';
            }

            $assertion .= ' {' . PHP_EOL;
            $assertion .= str_pad(' ', 12);
            $assertion .= 'return ' . implode(' && ', $conditions) . ';';
            $assertion .= PHP_EOL . str_pad(' ', 8) . '}';
        }

        $assertion .= ');';

        $this->assertions['mock'][] = $assertion;
    }

    private function buildFireStatement(FireStatement $statement)
    {
        $this->addImport($this->controller, 'Illuminate\\Support\\Facades\\Event');

        $this->setup['mock'][] = 'Event::fake();';

        $assertion = 'Event::assertDispatched(';

        if ($statement->isNamedEvent()) {
            $assertion .= $statement->event();
        } else {
            $this->addImport($this->controller, config('blueprint.namespace') . '\\Events\\' . $statement->event());
            $assertion .= $statement->event() . '::class';
        }

        if ($statement->data()) {
            $conditions = [];
            $variables = [];
            $assertion .= ', function ($event)';

            foreach ($statement->data() as $data) {
                if (Str::studly(Str::singular($data)) === $this->context) {
                    $variables[] .= '$' . $data;
                    $conditions[] .= sprintf('$event->%s->is($%s)', $data, $data);
                } else {
                    [$model, $property] = explode('.', $data);
                    $variables[] .= '$' . $model;
                    $conditions[] .= sprintf('$event->%s == $%s', $property ?? $model, str_replace('.', '->', $data()));
                }
            }

            if ($variables) {
                $assertion .= ' use (' . implode(', ', array_unique($variables)) . ')';
            }

            $assertion .= ' {' . PHP_EOL;
            $assertion .= str_pad(' ', 12);
            $assertion .= 'return ' . implode(' && ', $conditions) . ';';
            $assertion .= PHP_EOL . str_pad(' ', 8) . '}';
        }

        $assertion .= ');';

        $this->assertions['mock'][] = $assertion;
    }

    private function buildRenderStatement(RenderStatement $statement)
    {
        $this->tested_bits |= self::TESTS_VIEW;

        $view_assertions = [];
        $view_assertions[] = '$response->assertOk();';
        $view_assertions[] = sprintf('$response->assertViewIs(\'%s\');', $statement->view());

        foreach ($statement->data() as $data) {
            // TODO: if data references locally scoped var, strengthen assertion...
            $view_assertions[] = sprintf('$response->assertViewHas(\'%s\');', $data);
        }

        array_unshift($this->assertions['response'], ...$view_assertions);
    }

    private function buildRedirectStatement(RedirectStatement $statement)
    {
        $this->tested_bits |= self::TESTS_REDIRECT;

        $assertion = sprintf('$response->assertRedirect(route(\'%s\'', $statement->route());

        if ($statement->data()) {
            $parameters = array_map(function ($parameter) {
                return '$' . $parameter;
            }, $statement->data());

            $assertion .= ', [' . implode(', ', $parameters) . ']';
        } elseif (Str::contains($statement->route(), '.')) {
            [$model, $action] = explode('.', $statement->route());
            if (in_array($action, ['edit', 'update', 'show', 'destroy'])) {
                $assertion .= sprintf(", ['%s' => $%s]", $model, $model);
            }
        }

        $assertion .= '));';

        array_unshift($this->assertions['response'], $assertion);
    }

    private function buildResourceStatement(ResourceStatement $statement)
    {
        if ($this->name === 'store') {
            $this->assertions['response'][] = '$response->assertCreated();';
        } else {
            $this->assertions['response'][] = '$response->assertOk();';
        }

        $this->assertions['response'][] = '$response->assertJsonStructure([]);';
    }

    private function buildRespondStatement(RespondStatement $statement)
    {
        $this->tested_bits |= self::TESTS_RESPONDS;

        if ($statement->content()) {
            array_unshift($this->assertions['response'], '$response->assertJson($' . $statement->content() . ');');
        }

        if ($statement->status() === 200) {
            array_unshift($this->assertions['response'], '$response->assertOk();');
        } elseif ($statement->status() === 204) {
            array_unshift($this->assertions['response'], '$response->assertNoContent();');
        } else {
            array_unshift($this->assertions['response'], '$response->assertNoContent(' . $statement->status() . ');');
        }
    }

    private function buildSessionStatement(SessionStatement $statement)
    {
        $this->assertions['response'][] = sprintf('$response->assertSessionHas(\'%s\', %s);', $statement->reference(), '$' . str_replace('.', '->', $statement->reference()));
    }

    private function buildEloquentStatement(EloquentStatement $statement)
    {
        $this->addRefreshDatabaseTrait($this->controller);

        $model = $this->determineModel($this->controller->prefix(), $statement->reference());
        $this->addImport($this->controller, $this->modelNamespace . '\\' . $model);

        if ($statement->operation() === 'save') {
            $this->tested_bits |= self::TESTS_SAVE;

            if ($this->request_data) {
                $indent = str_pad(' ', 12);
                $plural = Str::plural($this->variable);
                $assertion = sprintf('$%s = %s::query()', $plural, $model);
                foreach ($this->request_data as $key => $datum) {
                    $assertion .= PHP_EOL . sprintf('%s->where(\'%s\', %s)', $indent, $key, $datum);
                }
                $assertion .= PHP_EOL . $indent . '->get();';

                $this->assertions['sanity'][] = $assertion;
                $this->assertions['sanity'][] = '$this->assertCount(1, $' . $plural . ');';
                $this->assertions['sanity'][] = sprintf('$%s = $%s->first();', $this->variable, $plural);
            } else {
                $this->assertions['generic'][] = '$this->assertDatabaseHas(' . Str::camel(Str::plural($model)) . ', [ /* ... */ ]);';
            }
        } elseif ($statement->operation() === 'find') {
            $this->setup['data'][] = sprintf('$%s = factory(%s::class)->create();', $this->variable, $model);
        } elseif ($statement->operation() === 'delete') {
            $this->tested_bits |= self::TESTS_DELETE;
            $this->setup['data'][] = sprintf('$%s = factory(%s::class)->create();', $this->variable, $model);
            $this->assertions['generic'][] = sprintf('$this->assertDeleted($%s);', $this->variable);
        } elseif ($statement->operation() === 'update') {
            $this->assertions['sanity'][] = sprintf('$%s->refresh();', $this->variable);

            if ($this->request_data) {
                foreach ($this->request_data as $key => $datum) {
                    $this->assertions['generic'][] = sprintf('$this->assertEquals(%s, $%s->%s);', $datum, $this->variable, $key);
                }
            }
        }
        return $model;
    }

    private function buildQueryStatement(QueryStatement $statement)
    {
        $this->addRefreshDatabaseTrait($this->controller);

        $this->setup['data'][] = sprintf('$%s = factory(%s::class, 3)->create();', Str::plural($this->variable), $this->model);

        $this->addImport($this->controller, $this->modelNamespace . '\\' . $this->determineModel($this->controller->prefix(), $statement->model()));
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
            return $name . '_behaves_as_expected';
        }

        $final_verification = array_pop($verifications);

        if (empty($verifications)) {
            return $name . '_' . $final_verification;
        }

        return $name . '_' . implode('_', $verifications) . '_and_' . $final_verification;
    }

    private function buildLines($lines)
    {
        return str_pad(' ', 8) . implode(PHP_EOL . str_pad(' ', 8), $lines);
    }

    private function uniqueSetupLines(array $setup)
    {
        return collect($setup)->filter()
            ->map(function ($lines) {
                return array_unique($lines);
            })
            ->toArray();
    }

    private function buildCall(): string
    {
        $call = sprintf('$response = $this->%s(route(\'%s.%s\'', $this->httpMethodForAction($this->name), Str::kebab($this->context), $this->name);

        if ($this->methodWithModel($this->name)) {
            $call .= ', $' . Str::camel($this->context);
        }
        $call .= ')';

        if ($this->request_data) {
            $call .= ', [';
            $call .= PHP_EOL;
            foreach ($this->request_data as $key => $datum) {
                $call .= str_pad(' ', 12);
                $call .= sprintf('\'%s\' => %s,', $key, $datum);
                $call .= PHP_EOL;
            }

            $call .= str_pad(' ', 8) . ']';
        }
        $call .= ');';
        return $call;
    }
}
