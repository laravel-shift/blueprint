<?php

namespace Blueprint;

use Illuminate\Filesystem\Filesystem;

class Builder
{
    public function execute(Blueprint $blueprint, Filesystem $files, string $draft, string $only = '', string $skip = '')
    {
        $cache = [];
        if ($files->exists('.blueprint')) {
            $cache = $blueprint->parse($files->get('.blueprint'));
        }

        $tokens = $blueprint->parse($files->get($draft));
        $tokens['cache'] = $cache['models'] ?? [];
        $registry = $blueprint->analyze($tokens);

        $only = array_filter(explode(',', $only));
        $skip = array_filter(explode(',', $skip));

        $generated = $blueprint->generate($registry, $only, $skip);

        $models = array_merge($tokens['cache'], $tokens['models'] ?? []);

        $files->put(
            '.blueprint',
            $blueprint->dump($generated + ($models ? ['models' => $models] : []))
        );

        return $generated;
    }
}
