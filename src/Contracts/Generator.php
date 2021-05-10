<?php

namespace Blueprint\Contracts;

use Blueprint\Tree;
use Illuminate\Filesystem\Filesystem;

interface Generator
{
    public function __construct(Filesystem $files);

    public function output(Tree $tree): array;

    public function types(): array;
}
