<?php

namespace Blueprint;

use Illuminate\Filesystem\Filesystem;

class Builder
{
    public static function execute(Blueprint $blueprint, Filesystem $files, string $draft)
    {
        $cache = [];
        if ($files->exists('.blueprint')) {
            $cache = $blueprint->parse($files->get('.blueprint'));
        }

        $tokens = $blueprint->parse($files->get($draft));
        $tokens['cache'] = $cache['models'] ?? [];
        $registry = $blueprint->analyze($tokens);
        $generated = $blueprint->generate($registry);

        $models = array_merge($tokens['cache'], $tokens['models'] ?? []);

        $files->put(
            '.blueprint',
            $blueprint->dump($generated + ($models ? ['models' => $models] : []))
        );

        return $generated;
    }
}
