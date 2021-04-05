<?php

namespace Blueprint\Lexers;

use Blueprint\Contracts\Lexer;

use function preg_split;

class SeederLexer implements Lexer
{
    public function analyze(array $tokens): array
    {
        $registry = [
            'seeders' => [],
        ];

        if (! empty($tokens['seeders'])) {
            $registry['seeders'] = $this->analyzeValue($tokens['seeders']);
        }

        return $registry;
    }

    private function analyzeValue($value)
    {
        return preg_split('/,([ \t]+)?/', $value);
    }
}
