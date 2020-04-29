<?php

namespace Blueprint;

class FileMixins
{
    private $stubs = [];

    public function stub()
    {
        return function ($path) {
            if (!isset($this->stubs[$path])) {
                $this->stubs[$path] = $this->get(STUBS_PATH . '/' . $path);
            }

            return $this->stubs[$path];
        };
    }
}
