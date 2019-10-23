<?php

require 'vendor/autoload.php';

use Blueprint\Lexers\Lexer;
use Blueprint\Parsers\Parser;
use Blueprint\Generators\Generator;

$contents = file_get_contents('sample.yaml');

$tokens = Parser::parse($contents);
$registry = Lexer::analyze($tokens);
Generator::generate($registry);

