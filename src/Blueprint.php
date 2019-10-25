<?php

namespace Blueprint;


use Symfony\Component\Yaml\Yaml;

class Blueprint
{
    private $lexers = [];
    private $generators = [];

    public function parse($content)
    {
        $content = preg_replace('/^(\s+)(id|timestamps)$/m', '$1$2: $2', $content);

        return Yaml::parse($content);
    }

    public function analyze(array $tokens)
    {
        $registry = [
            'models' => [],
            'controllers' => []
        ];

        foreach ($this->lexers as $lexer) {
            $registry = array_merge($registry, $lexer->analyze($tokens));
        }

        return $registry;
    }

    public function generate(array $tree)
    {
        foreach ($this->generators as $generator) {
            $generator->output($tree);
        }
    }

    public function registerLexer($lexer)
    {
        $this->lexers[] = $lexer;
    }

    public function registerGenerator($generator)
    {
        $this->generators[] = $generator;
    }
}