<?php

namespace Tests\Traits;

trait MocksFilesystem
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->files = $this->mock(\Illuminate\Filesystem\Filesystem::class);
        $this->swap('files', $this->files);
    }
}
