<?php

namespace Blueprint;

class Builder
{
    public static function execute(Blueprint $blueprint, string $draft)
    {
        // TODO: read in previous models...

        $tokens = $blueprint->parse($draft);
        $registry = $blueprint->analyze($tokens);
        $generated = $blueprint->generate($registry);

        // TODO: save to .blueprint

        return $generated;
    }
}
