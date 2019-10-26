<?php

namespace Blueprint\Contracts;

interface Generator
{
    public function output(array $tree): void;
}