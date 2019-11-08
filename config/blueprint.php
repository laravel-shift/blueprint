<?php

return [
    'generators' => [
        \Blueprint\Generators\MigrationGenerator::class,
        \Blueprint\Generators\ModelGenerator::class,
        \Blueprint\Generators\FactoryGenerator::class,
    ],

    'lexers' => [
        \Blueprint\Lexers\ModelLexer::class,
    ],
];
