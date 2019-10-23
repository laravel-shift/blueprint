<?php


namespace Blueprint\Lexers;

class Lexer
{
    public static function analyze(array $tokens)
    {
        $registry = [];

        $modelLexer = new ModelLexer();
        $registry = array_merge($registry, $modelLexer->analyze($tokens));

        return $registry;
    }


}