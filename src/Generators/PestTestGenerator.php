<?php

namespace Blueprint\Generators;

use Blueprint\Blueprint;
use Blueprint\Concerns\HandlesImports;
use Blueprint\Concerns\HandlesTraits;
use Blueprint\Contracts\Generator;
use Blueprint\Contracts\Model as BlueprintModel;
use Blueprint\Models\Column;
use Blueprint\Models\Controller;
use Blueprint\Models\Model;
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

class PestTestGenerator extends AbstractClassGenerator implements Generator
{
    use HandlesImports, HandlesTraits;

    const TESTS_VIEW = 1;

    const TESTS_REDIRECT = 2;

    const TESTS_SAVE = 4;

    const TESTS_DELETE = 8;

    const TESTS_RESPONDS = 16;

    protected $stubs = [];

    protected $types = ['controllers', 'tests'];

    public function output(Tree $tree): array
    {
        $this->tree = $tree;

        $stub = $this->filesystem->stub('pest.test.class.stub');

        /** @var \Blueprint\Models\Controller $controller */
        foreach ($tree->controllers() as $controller) {
            $path = $this->getPath($controller);

            $this->create($path, $this->populateStub($stub, $controller));
        }

        return $this->output;
    }

    protected function getPath(BlueprintModel $model)
    {
        $path = str_replace('\\', '/', Blueprint::relativeNamespace($model->fullyQualifiedClassName()));

        return 'tests/Feature/' . $path . 'Test.php';
    }

    protected function populateStub(string $stub, Controller $controller)
    {
        $stub = str_replace('{{ namespace }}', 'Tests\\Feature\\' . Blueprint::relativeNamespace($controller->fullyQualifiedNamespace()), $stub);
        $stub = str_replace('{{ namespacedClass }}', '\\' . $controller->fullyQualifiedClassName(), $stub);
        $stub = str_replace('{{ class }}', $controller->className() . 'Test', $stub);
        $stub = str_replace('{{ body }}', $this->buildTestCases($controller), $stub);
        $stub = str_replace('{{ imports }}', $this->buildImports($controller), $stub);

        return trim($stub) . PHP_EOL;
    }

    protected function buildTestCases(Controller $controller)
    {
        if (empty($controller->methods())) {
            return '';
        }

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
                ? config('blueprint.namespace') . '\\' . config('blueprint.models_namespace')
                : config('blueprint.namespace');

            if (in_array($name, ['edit', 'update', 'show', 'destroy'])) {
                $this->addImport($controller, $modelNamespace . '\\' . $model);

                $setup['data'][] = sprintf('$%s = %s::factory()->create();', $variable, $model);
            }

            foreach ($statements as $statement) {
                if ($statement instanceof SendStatement) {
                    if ($statement->isNotification()) {
                        $this->addImport($controller, 'Illuminate\\Support\\Facades\\Notification');
                        $this->addImport($controller, config('blueprint.namespace') . '\\Notification\\' . $statement->mail());

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
                                if (Str::studly(Str::singular($data)) === $context || !Str::contains($data, '.')) {
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
                            $assertion .= str_pad(' ', 8);
                            $assertion .= 'return ' . implode(' && ', $conditions) . ';';
                            $assertion .= PHP_EOL . str_pad(' ', 4) . '}';
                        }

                        $assertion .= ');';

                        $assertions['mock'][] = $assertion;
                    } else {
                        $this->addImport($controller, 'Illuminate\\Support\\Facades\\Mail');
                        $this->addImport($controller, config('blueprint.namespace') . '\\Mail\\' . $statement->mail());

                        $setup['mock'][] = 'Mail::fake();';

                        $assertion = sprintf('Mail::assertSent(%s::class', $statement->mail());

                        if ($statement->data() || $statement->to()) {
                            $conditions = [];
                            $variables = [];
                            $assertion .= ', function ($mail)';

                            if ($statement->to()) {
                                $conditions[] = '$mail->hasTo($' . str_replace('.', '->', $statement->to()) . ')';
                            }

                            foreach ($statement->data() as $data) {
                                if (Str::studly(Str::singular($data)) === $context || !Str::contains($data, '.')) {
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
                            $assertion .= str_pad(' ', 8);
                            $assertion .= 'return ' . implode(' && ', $conditions) . ';';
                            $assertion .= PHP_EOL . str_pad(' ', 4) . '}';
                        }

                        $assertion .= ');';

                        $assertions['mock'][] = $assertion;
                    }
                } elseif ($statement instanceof ValidateStatement) {
                    $class = $this->buildFormRequestName($controller, $name);
                    $test_case = $this->buildFormRequestTestCase($controller->fullyQualifiedClassName(), $name, config('blueprint.namespace') . '\\Http\\Requests\\' . $class) . PHP_EOL . PHP_EOL . $test_case;

                    if ($statement->data()) {
                        foreach ($statement->data() as $data) {
                            [$qualifier, $column] = $this->splitField($data);

                            if (is_null($qualifier)) {
                                $qualifier = $context;
                            }

                            $variable_name = $data;

                            /** @var \Blueprint\Models\Model $local_model */
                            $local_model = $this->tree->modelForContext($qualifier);

                            if (!is_null($local_model) && $local_model->hasColumn($column)) {
                                $local_column = $local_model->column($column);

                                $factory = $this->generateReferenceFactory($local_column, $controller, $modelNamespace);

                                if ($factory) {
                                    [$faker, $variable_name] = $factory;
                                } else {
                                    $this->addImport($controller, 'function Pest\\Faker\\fake');

                                    $faker = sprintf('$%s = fake()->%s;', $data, FakerRegistry::fakerData($local_column->name()) ?? FakerRegistry::fakerDataType($local_model->column($column)->dataType()));
                                }

                                $setup['data'][] = $faker;
                                $request_data[$data] = '$' . $variable_name;
                            } elseif (!is_null($local_model)) {
                                foreach ($local_model->columns() as $local_column) {
                                    if (in_array($local_column->name(), ['id', 'softdeletes', 'softdeletestz'])) {
                                        continue;
                                    }

                                    if (in_array('nullable', $local_column->modifiers())) {
                                        continue;
                                    }

                                    $factory = $this->generateReferenceFactory($local_column, $controller, $modelNamespace);
                                    if ($factory) {
                                        [$faker, $variable_name] = $factory;
                                    } else {
                                        $this->addImport($controller, 'function Pest\\Faker\\fake');

                                        $faker = sprintf('$%s = fake()->%s;', $local_column->name(), FakerRegistry::fakerData($local_column->name()) ?? FakerRegistry::fakerDataType($local_column->dataType()));
                                        $variable_name = $local_column->name();
                                    }

                                    $setup['data'][] = $faker;
                                    $request_data[$local_column->name()] = '$' . $variable_name;
                                }
                            }
                        }
                    }
                } elseif ($statement instanceof DispatchStatement) {
                    $this->addImport($controller, 'Illuminate\\Support\\Facades\\Queue');
                    $this->addImport($controller, config('blueprint.namespace') . '\\Jobs\\' . $statement->job());

                    $setup['mock'][] = 'Queue::fake();';

                    $assertion = sprintf('Queue::assertPushed(%s::class', $statement->job());

                    if ($statement->data()) {
                        $conditions = [];
                        $variables = [];
                        $assertion .= ', function ($job)';

                        foreach ($statement->data() as $data) {
                            if (Str::studly(Str::singular($data)) === $context || !Str::contains($data, '.')) {
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
                        $assertion .= str_pad(' ', 8);
                        $assertion .= 'return ' . implode(' && ', $conditions) . ';';
                        $assertion .= PHP_EOL . str_pad(' ', 4) . '}';
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
                        $this->addImport($controller, config('blueprint.namespace') . '\\Events\\' . $statement->event());
                        $assertion .= $statement->event() . '::class';
                    }

                    if ($statement->data()) {
                        $conditions = [];
                        $variables = [];
                        $assertion .= ', function ($event)';

                        foreach ($statement->data() as $data) {
                            if (Str::studly(Str::singular($data)) === $context || !Str::contains($data, '.')) {
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
                        $assertion .= str_pad(' ', 8);
                        $assertion .= 'return ' . implode(' && ', $conditions) . ';';
                        $assertion .= PHP_EOL . str_pad(' ', 4) . '}';
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

                    $assertion = sprintf(
                        '$response->assertRedirect(route(\'%s\'',
                        config('blueprint.plural_routes') ? Str::plural(Str::kebab($statement->route())) : Str::kebab($statement->route())
                    );

                    if ($statement->data()) {
                        $parameters = array_map(fn ($parameter) => '$' . $parameter, $statement->data());

                        $assertion .= ', [' . implode(', ', $parameters) . ']';
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
                        array_unshift($assertions['response'], '$response->assertJson($' . $statement->content() . ');');
                    }

                    if ($statement->status() === 200) {
                        array_unshift($assertions['response'], '$response->assertOk();');
                    } elseif ($statement->status() === 204) {
                        array_unshift($assertions['response'], '$response->assertNoContent();');
                    } else {
                        array_unshift($assertions['response'], '$response->assertNoContent(' . $statement->status() . ');');
                    }
                } elseif ($statement instanceof SessionStatement) {
                    $assertions['response'][] = sprintf('$response->assertSessionHas(\'%s\', %s);', $statement->reference(), '$' . str_replace('.', '->', $statement->reference()));
                } elseif ($statement instanceof EloquentStatement) {
                    $model = $this->determineModel($controller->prefix(), $statement->reference());
                    $this->addImport($controller, $modelNamespace . '\\' . $model);

                    if ($statement->operation() === 'save') {
                        $tested_bits |= self::TESTS_SAVE;

                        if ($request_data) {
                            $indent = str_pad(' ', 8);
                            $plural = Str::plural($variable);
                            $assertion = sprintf('$%s = %s::query()', $plural, $model);
                            foreach ($request_data as $key => $datum) {
                                $assertion .= PHP_EOL . sprintf('%s->where(\'%s\', %s)', $indent, $key, $datum);
                            }
                            $assertion .= PHP_EOL . $indent . '->get();';

                            $assertions['sanity'][] = $assertion;
                            $assertions['sanity'][] = 'expect($' . $plural . ')->toHaveCount(1);';
                            $assertions['sanity'][] = sprintf('$%s = $%s->first();', $variable, $plural);
                        } else {
                            $this->addImport($controller, 'function Pest\\Laravel\\assertDatabaseHas');

                            $assertions['generic'][] = 'assertDatabaseHas(' . Str::camel(Str::plural($model)) . ', [ /* ... */ ]);';
                        }
                    } elseif ($statement->operation() === 'find') {
                        $setup['data'][] = sprintf('$%s = %s::factory()->create();', $variable, $model);
                    } elseif ($statement->operation() === 'delete') {
                        $tested_bits |= self::TESTS_DELETE;
                        $setup['data'][] = sprintf('$%s = %s::factory()->create();', $variable, $model);

                        /** @var \Blueprint\Models\Model $local_model */
                        $local_model = $this->tree->modelForContext($model);
                        if (!is_null($local_model) && $local_model->usesSoftDeletes()) {
                            $this->addImport($controller, 'function Pest\\Laravel\\assertSoftDeleted');

                            $assertions['generic'][] = sprintf('assertSoftDeleted($%s);', $variable);
                        } else {
                            $this->addImport($controller, 'function Pest\\Laravel\\assertModelMissing');

                            $assertions['generic'][] = sprintf('assertModelMissing($%s);', $variable);
                        }
                    } elseif ($statement->operation() === 'update') {
                        $assertions['sanity'][] = sprintf('$%s->refresh();', $variable);

                        if ($request_data) {
                            /** @var \Blueprint\Models\Model $local_model */
                            $local_model = $this->tree->modelForContext($model);
                            foreach ($request_data as $key => $datum) {
                                if (!is_null($local_model) && $local_model->hasColumn($key) && $local_model->column($key)->dataType() === 'date') {
                                    $this->addImport($controller, 'Carbon\\Carbon');
                                    $assertions['generic'][] = sprintf('expect(Carbon::parse(%s))->toEqual($%s->%s);', $datum, $variable, $key);
                                } else {
                                    $assertions['generic'][] = sprintf('expect(%s)->toEqual($%s->%s);', $datum, $variable, $key);
                                }
                            }
                        }
                    }
                } elseif ($statement instanceof QueryStatement) {
                    $setup['data'][] = sprintf('$%s = %s::factory()->count(3)->create();', Str::plural($variable), $model);

                    $this->addImport($controller, $modelNamespace . '\\' . $this->determineModel($controller->prefix(), $statement->model()));
                }
            }

            $call = sprintf(
                '$response = %s(route(\'%s.%s\'',
                $this->httpMethodForAction($name),
                config('blueprint.plural_routes') ? Str::plural(Str::kebab($context)) : Str::kebab($context),
                $name
            );

            $this->addImport($controller, 'function Pest\\Laravel\\' . $this->httpMethodForAction($name));

            if (in_array($name, ['edit', 'update', 'show', 'destroy'])) {
                $call .= ', $' . Str::camel($context);
            }
            $call .= ')';

            if ($request_data) {
                $call .= ', [';
                $call .= PHP_EOL;
                foreach ($request_data as $key => $datum) {
                    $call .= str_pad(' ', 8);
                    $call .= sprintf('\'%s\' => %s,', $key, $datum);
                    $call .= PHP_EOL;
                }

                $call .= str_pad(' ', 4) . ']';
            }
            $call .= ');';

            $body = implode(PHP_EOL . PHP_EOL, array_map([$this, 'buildLines'], $this->uniqueSetupLines($setup)));
            $body .= PHP_EOL . PHP_EOL;
            $body .= str_pad(' ', 4) . $call;
            $body .= PHP_EOL . PHP_EOL;
            $body .= implode(PHP_EOL . PHP_EOL, array_map([$this, 'buildLines'], array_filter($assertions)));

            $test_case_name = $this->buildTestCaseName($name, $tested_bits);
            $test_case_name = Str::of($test_case_name)->headline()->lower()->toString();
            $test_case = str_replace('{{ method }}', $test_case_name, $test_case);
            $test_case = str_replace('{{ body }}', trim($body), $test_case);

            $test_cases .= PHP_EOL . $test_case . PHP_EOL;
        }

        return trim($this->buildTraits($controller) . PHP_EOL . $test_cases);
    }

    private function testCaseStub()
    {
        if (empty($this->stubs['test-case'])) {
            $this->stubs['test-case'] = $this->filesystem->stub('pest.test.case.stub');
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
            return $controller->name() . Str::studly($name) . 'Request';
        }

        return $controller->namespace() . '\\' . $controller->name() . Str::studly($name) . 'Request';
    }

    private function buildFormRequestTestCase(string $controller, string $action, string $form_request)
    {
        return <<< END
test('${action} uses form request validation')
    ->assertActionUsesFormRequest(
        \\${controller}::class,
        '${action}',
        \\${form_request}::class
    );
END;
    }

    private function httpMethodForAction($action)
    {
        return match ($action) {
            'store' => 'post',
            'update' => 'put',
            'destroy' => 'delete',
            default => 'get',
        };
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
        return str_pad(' ', 4) . implode(PHP_EOL . str_pad(' ', 4), $lines);
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
            ->map(fn ($lines) => array_unique($lines))
            ->toArray();
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

        $faker = sprintf('$%s = %s::factory()->create();', Str::beforeLast($local_column->name(), '_id'), Str::studly($reference));

        $this->addImport($controller, $modelNamespace . '\\' . Str::studly($reference));

        return [$faker, $variable_name];
    }
}
