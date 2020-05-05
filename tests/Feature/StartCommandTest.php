<?php

namespace Tests\Unit;

use Blueprint\Blueprint;
use Blueprint\Commands\StartCommand;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class StartCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_exits_with_error_if_draft_file_already_exists()
    {
        
    }

    /**
     * @test
     */
    public function it_creates_a_draft_file_if_none_exists()
    {
        $file = \Mockery::mock(Filesystem::class);
        $file->expects('put')
        ->with('draft.yaml');

        // (new StartCommand())->handle();
        // not sure how to call this method
    }
}
