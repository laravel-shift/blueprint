<?php

namespace Blueprint\Contracts;

use Blueprint\Tree;

interface Generator
{
    /**
     * @param \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function __construct($files);

    public function output(Tree $tree): array;

    public function types(): array;
}
