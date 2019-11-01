<?php

namespace Blueprint\Contracts;

use Illuminate\Filesystem\Filesystem;

interface Generator
{
    public function __construct(Filesystem $files);

    public function output(array $tree): array;
}