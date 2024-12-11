<?php

namespace Blueprint\Lexers;

use Blueprint\Contracts\Lexer;
use Blueprint\Models\Component;
use Illuminate\Support\Str;

class ComponentLexer implements Lexer
{
    private StatementLexer $statementLexer;

    public function __construct(StatementLexer $statementLexer)
    {
        $this->statementLexer = $statementLexer;
    }

    public function analyze(array $tokens): array
    {
        $registry = [
            'components' => [],
        ];

        if (empty($tokens['components'])) {
            return $registry;
        }

        foreach ($tokens['components'] as $name => $definition) {
            $component = new Component($name);

            if (isset($definition['mount'])) {
                foreach (explode(',', $definition['mount']) as $property) {
                    $component->addProperty(trim($property));
                }

                unset($definition['mount']);
            }

            $definition = array_merge($this->defaultRenderTokens($name), $definition);

            foreach ($definition as $method => $body) {
                $component->addMethod($method, $this->statementLexer->analyze($body));
            }

            $registry['components'][$name] = $component;
        }

        return $registry;
    }

    private function defaultRenderTokens(string $name): array
    {
        return [
            'render' => [
                'render' => 'livewire.' . Str::snake($name, '-'),
            ],
        ];
    }
}
