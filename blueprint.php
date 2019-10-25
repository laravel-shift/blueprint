<?php

require 'vendor/autoload.php';

use Blueprint\Blueprint;

$contents = file_get_contents('sample.yaml');

$blueprint = new Blueprint();
 $blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
 $blueprint->registerGenerator(new \Blueprint\Generators\MigrationGenerator());
 $blueprint->registerGenerator(new \Blueprint\Generators\ModelGenerator());
 $blueprint->registerGenerator(new \Blueprint\Generators\FactoryGenerator());

$tokens = $blueprint->parse($contents);
$registry = $blueprint->analyze($tokens);
$blueprint->generate($registry);

