<?php

namespace Blueprint\Contracts;

interface Lexer
{
    public function analyze(array $tokens): array;
}
