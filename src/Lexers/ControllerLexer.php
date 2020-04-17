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
            $controller = new Controller($name);

            if ($this->isResource($definition)) {
                $original = $definition;
                $definition = $this->generateResourceTokens($controller, $this->methodsForResource($definition['resource']));
                // unset shorthand
                unset($original['resource']);
                // this gives the ability to both use a shorthand and override some methods
                $definition = array_merge($definition, $original);
            }

            foreach ($definition as $method => $body) {
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

    private function generateResourceTokens(Controller $controller, array $methods)
    {
        return collect($this->resourceTokens())
            ->filter(function ($statements, $method) use ($methods) {
                return in_array($method, $methods);
            })
            ->mapWithKeys(function ($statements, $method) use ($controller) {
                return [
                    str_replace('api.', '', $method) => collect($statements)->map(function ($statement) use ($controller) {
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

            'api.index' => [
                'query' => 'all:[plural]',
                'resource' => 'collection:[plural]',
            ],
            'api.store' => [
                'validate' => '[singular]',
                'save' => '[singular]',
                'resource' => '[singular]',
            ],
            'api.show' => [
                'resource' => '[singular]',
            ],
            'api.update' => [
                'validate' => '[singular]',
                'update' => '[singular]',
                'resource' => '[singular]',
            ],
            'api.destroy' => [
                'delete' => '[singular]',
                'respond' => 200,
            ],
        ];
    }

    private function methodsForResource(string $type)
    {
        if ($type === 'api') {
            return ['api.index', 'api.store', 'api.show', 'api.update', 'api.destroy'];
        }

        if ($type === 'all') {
            return ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        }

        return array_map('trim', explode(',', $type));
    }
}
