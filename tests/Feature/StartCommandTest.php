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
    public function it_creates_a_draft_file_if_none_exists()
    {
        $file = \Mockery::mock(Filesystem::class);

        $file->shouldReceive('exists')
        ->andReturn(false);

        $file->expects('stub')
        ->with('draft.stub')
        ->andReturn('stub');

        $file->expects('put')
        ->with('draft.yaml', 'stub');

        (new StartCommand($file))->handle();
        // $this->info tries to printLn, but errors out because we're not calling the command through a console
    }
}
