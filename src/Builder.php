<?php

namespace Blueprint;

use Illuminate\Filesystem\Filesystem;

class Builder
{
    public function execute(Blueprint $blueprint, Filesystem $files, string $draft, string $only = '', string $skip = '', $overwriteMigrations = false)
    {
        $cache = [];
        if ($files->exists('.blueprint')) {
            $cache = $blueprint->parse($files->get('.blueprint'));
        }

        $contents = $files->get($draft);
        $using_indexes = preg_match('/^\s+indexes:\R/m', $contents) !== 1;

        $tokens = $blueprint->parse($contents, $using_indexes);
        $tokens['cache'] = $cache['models'] ?? [];
        $registry = $blueprint->analyze($tokens);

        $only = array_filter(explode(',', $only));
        $skip = array_filter(explode(',', $skip));

        $generated = $blueprint->generate($registry, $only, $skip, $overwriteMigrations);

        $models = array_merge($tokens['cache'], $tokens['models'] ?? []);

        $files->put(
            '.blueprint',
            $blueprint->dump($generated + ($models ? ['models' => $models] : []))
        );

        return $generated;
    }
}
