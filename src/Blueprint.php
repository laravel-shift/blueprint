<?php

namespace Blueprint;

use Blueprint\Contracts\Lexer;
use Symfony\Component\Yaml\Yaml;
use Blueprint\Contracts\Generator;

class Blueprint
{
    private $lexers = [];
    private $generators = [];

    public function parse($content)
    {
        $content = preg_replace_callback('/^(\s+)(id|timestamps(Tz)?|softDeletes(Tz)?)$/mi', function ($matches) {
            return $matches[1] . strtolower($matches[2]) . ': ' . $matches[2];
        }, $content);

        return Yaml::parse($content);
    }

    public function analyze(array $tokens)
    {
        $registry = [
            'models' => [],
            'controllers' => [],
        ];

        foreach ($this->lexers as $lexer) {
            $registry = array_merge($registry, $lexer->analyze($tokens));
        }

        return $registry;
    }

    public function generate(array $tree): array
    {
        $components = [];

        foreach ($this->generators as $generator) {
            $components = array_merge_recursive($components, $generator->output($tree));
        }

        return $components;
    }

    public function dump(array $generated)
    {
        return Yaml::dump($generated);
    }

    public function registerLexer(Lexer $lexer)
    {
        $this->lexers[] = $lexer;
    }

    public function registerGenerator(Generator $generator)
    {
        $this->generators[] = $generator;
    }
}
