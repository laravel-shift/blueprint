<?php

require 'vendor/autoload.php';

use Blueprint\Blueprint;

$contents = file_get_contents('sample.yaml');

$blueprint = new Blueprint();
// $blueprint->registerLexer(new Lexer());

$tokens = $blueprint->parse($contents);
$registry = $blueprint->analyze($tokens);
$blueprint->generate($tokens);

