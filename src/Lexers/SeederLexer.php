<?php

namespace Blueprint\Lexers;

use Blueprint\Contracts\Lexer;
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
use Illuminate\Support\Str;

class SeederLexer implements Lexer
{
    public function analyze(array $tokens): array
    {
        $registry = [
            'seeders' => [],
        ];

        if (!empty($tokens['seeders'])) {
            $registry['seeders'] = $this->analyzeValue($tokens['seeders']);
        }

        return $registry;
    }

    private function analyzeValue($value)
    {
        return preg_split('/,([ \t]+)?/', $value);
    }
}
