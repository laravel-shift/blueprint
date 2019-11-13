<?php

namespace Blueprint\Lexers;

use Blueprint\Contracts\Lexer;
use Blueprint\Controller;
use Blueprint\Models\Statements\QueryStatement;
use Blueprint\Models\Statements\RenderStatement;

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

            foreach ($definition as $method => $body) {
                $controller->addMethod($method, $this->statementLexer->analyze($body));
            }

            $registry['controllers'][$name] = $controller;
        }

        return $registry;
    }
}