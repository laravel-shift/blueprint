<?php

namespace Blueprint\Models;

use Blueprint\Blueprint;
use Illuminate\Filesystem\Filesystem;

class Token
{
    public static function get(Blueprint $blueprint, Filesystem $files, string $draft, bool $with_cache = true)
    {
        $tokens = $blueprint->parse($files->get($draft));

        if ($with_cache) {
            $cache = [];
            if ($files->exists('.blueprint')) {
                $cache = $blueprint->parse($files->get('.blueprint'));
            }

            $tokens['cache'] = $cache['models'] ?? [];
        }

        return new TokensCollection($tokens);
    }
}
