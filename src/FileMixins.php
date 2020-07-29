<?php

namespace Blueprint;

class FileMixins
{
    private $stubs = [];

    public function stub()
    {
        return function ($path) {
            if (!isset($this->stubs[$path])) {
                $stubPath = file_exists($customPath = CUSTOM_STUBS_PATH . '/' . $path)
                          ? $customPath
                          : STUBS_PATH . '/' . $path;
                $this->stubs[$path] = $this->get($stubPath);
            }

            return $this->stubs[$path];
        };
    }
}
