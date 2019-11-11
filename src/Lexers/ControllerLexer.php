<?php

namespace Blueprint\Lexers;

use Blueprint\Controller;

class ControllerLexer
{
    public function analyze(array $tokens): array
    {
        $registry = ['controllers' => []];

        if (empty($tokens['controllers'])) {
            return $registry;
        }

        foreach ($tokens['controllers'] as $name => $definition) {
            $controller = new Controller($name);

            foreach ($definition as $method => $statements) {
                // TODO: lex the statements into "Statement" objects

                $controller->addMethod($method, $statements);
            }

            $registry['controllers'][$name] = $controller;
        }

        return $registry;
    }
}