<?php

namespace Blueprint\Generators;

use Blueprint\Lexers\ModelLexer;

class Generator
{
    public static function generate(array $registry)
    {
        $generator = new MigrationGenerator();
        $generator->output($registry);

        $generator = new ModelGenerator();
        $generator->output($registry);

        $generator = new FactoryGenerator();
        $generator->output($registry);
    }
}