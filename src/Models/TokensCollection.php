<?php

namespace Blueprint\Models;

use Illuminate\Support\Collection;

class TokensCollection extends Collection
{
    public function toModel()
    {
        $models = array_merge($this->items['cache'], $this->items['models'] ?? []);

        return $models ? ['models' => $models] : [];
    }
}
