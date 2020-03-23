<?php

namespace Blueprint\Lexers;

use Blueprint\Contracts\Lexer;
use Blueprint\Models\Controller;
use Illuminate\Support\Str;

class ControllerLexer implements Lexer
{
    /**
     * @var StatementLexer
     */
    private $statementLexer;

    public function __construct(StatementLexer $statementLexer)
    {
        $this->statementLexer = $statementLexer;
    }

    public function analyze(array $tokens): array
    {
        $registry = ['controllers' => []];

        if (empty($tokens['controllers'])) {
            return $registry;
        }

        foreach ($tokens['controllers'] as $name => $definition) {
            $controller = new Controller($name, $this->isAPI($definition));

            if ($this->isResource($definition)) {
                $original = $definition;
                $definition = $this->generateResourceTokens($controller, $this->methodsForResource($definition['resource']));
                // unset shorthand
                unset($original['resource']);
                // this gives the ability to both use a shorthand and override some methods
                $definition = array_merge($definition, $original);
            }

            foreach ($definition as $method => $body) {
                if (isset($body['resource'])) {
                    $controller->setAPI();
                }
                $controller->addMethod($method, $this->statementLexer->analyze($body));
            }

            $registry['controllers'][$name] = $controller;
        }

        return $registry;
    }

    private function isResource(array $definition)
    {
        return isset($definition['resource']) && is_string($definition['resource']);
    }

    private function isAPI(array $definition)
    {
        return isset($definition['resource']) &&
            is_string($definition['resource']) &&
            $definition['resource'] === 'api';
    }

    private function generateResourceTokens(Controller $controller, array $methods)
    {
        return collect($this->resourceTokens())
            ->filter(function ($statements, $method) use ($methods) {
                return in_array($method, $methods);
            })
            ->mapWithKeys(function ($statements, $method) use ($controller) {
                return [
                    str_replace('.api', '', $method) => collect($statements)->map(function ($statement) use (
                        $controller
                    ) {
                        $model = Str::singular($controller->prefix());

                        return str_replace(
                            ['[singular]', '[plural]'],
                            [Str::lower($model), Str::lower(Str::plural($model))],
                            $statement
                        );
                    }),
                ];
            })
            ->toArray();
    }

    private function resourceTokens()
    {
        return [
            'index' => [
                'query' => 'all:[plural]',
                'render' => '[singular].index with [plural]',
            ],
            'create' => [
                'render' => '[singular].create',
            ],
            'store' => [
                'validate' => '[singular]',
                'save' => '[singular]',
                'flash' => '[singular].id',
                'redirect' => '[singular].index',
            ],
            'show' => [
                'render' => '[singular].show with:[singular]',
            ],
            'edit' => [
                'render' => '[singular].edit with:[singular]',
            ],
            'update' => [
                'validate' => '[singular]',
                'update' => '[singular]',
                'flash' => '[singular].id',
                'redirect' => '[singular].index',
            ],
            'destroy' => [
                'delete' => '[singular]',
                'redirect' => '[singular].index',
            ],

            'index.api' => [
                'query' => 'all:[plural]',
                'resource' => '[singular] collection',
            ],
            'store.api' => [
                'validate' => '[singular]',
                'save' => '[singular]',
                'resource' => '[singular]',
            ],
            'show.api' => [
                'authorize' => '[singular]',
                'resource' => '[singular]',
            ],
            'update.api' => [
                'authorize' => '[singular]',
                'validate' => '[singular]',
                'update' => '[singular]',
                'resource' => 'empty',
            ],
            'destroy.api' => [
                'authorize' => '[singular]',
                'delete' => '[singular]',
                'resource' => 'empty',
            ],
        ];
    }

    private function methodsForResource(string $type)
    {
        if ($type === 'api') {
            return ['index.api', 'store.api', 'show.api', 'update.api', 'destroy.api'];
        }

        if ($type === 'all') {
            return ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        }

        return array_map('trim', explode(',', $type));
    }
}
