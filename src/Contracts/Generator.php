<?php

namespace Blueprint\Contracts;

interface Generator
{
    /**
     * @param \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function __construct($files);

    public function output(array $tree): array;

    public function types(): array;
}
