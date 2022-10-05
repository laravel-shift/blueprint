<?php

namespace Blueprint\Lexers;

use Blueprint\Contracts\Lexer;
use Illuminate\Container\Container;

class ConfigLexer implements Lexer
{
    private $app;

    public function __construct(Container $app = null)
    {
        $this->app = $app ?? Container::getInstance();
    }

    public function analyze(array $tokens): array
    {
        if (array_key_exists('config', $tokens) && is_array($tokens['config'])) {
            $this->analyzeValue($tokens['config']);
        }

        return [];
    }

    private function analyzeValue(array $config): void
    {
        $this->app['config']->set(
            'blueprint',
            array_merge(
                $this->app['config']->get('blueprint'),
                $config
            )
        );
    }
}
