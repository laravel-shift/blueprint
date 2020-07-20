<?php

namespace Blueprint;

use Blueprint\Models\Token;
use Illuminate\Filesystem\Filesystem;

class Builder
{
    public static function execute(Blueprint $blueprint, Filesystem $files, string $draft, string $only = '', string $skip = '')
    {
        $tokens = Token::get($blueprint, $files, $draft);

        // todo why we analyze everything?
        $registry = $blueprint->analyze($tokens->toArray());

        $only = array_filter(explode(',', $only));
        $skip = array_filter(explode(',', $skip));

        $generated = $blueprint->generate($registry, $only, $skip);

        $files->put(
            '.blueprint',
            $blueprint->dump(array_merge($generated,$tokens->toModel()))
        );

        return $generated;
    }
}
