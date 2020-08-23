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

            if (isset($definition['resource'])) {
                $resource_methods = $this->methodsForResource($definition['resource']);
                $resource_definition = $this->generateResourceTokens($controller, $resource_methods);

                if ($this->hasOnlyApiResourceMethods($resource_methods)) {
                    $controller->setApiResource(true);
                }

                unset($definition['resource']);

                $definition = array_merge($resource_definition, $definition);
            }

            foreach ($definition as $method => $body) {
                $controller->addMethod($method, $this->statementLexer->analyze($body));
            }

            $registry['controllers'][$name] = $controller;
        }

        return $registry;
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
                            [Str::camel($model), Str::camel(Str::plural($model))],
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
                'respond' => 204,
            ],
        ];
    }

    private function methodsForResource(string $type)
    {
        if ($type === 'api') {
            return ['api.index', 'api.store', 'api.show', 'api.update', 'api.destroy'];
        }

        if ($type === 'web') {
            return ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        }

        return array_map('trim', explode(',', strtolower($type)));
    }

    private function hasOnlyApiResourceMethods(array $methods)
    {
        return collect($methods)->every(function ($item, $key) {
            return Str::startsWith($item, 'api.');
        });
    }
}
