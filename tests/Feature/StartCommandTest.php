<?php

namespace Tests\Unit;

use Blueprint\Blueprint;
use Blueprint\Commands\StartCommand;
use Blueprint\Commands\TraceCommand;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class StartCommandTest extends TestCase
{

    /**
     * @test
     */
    public function it_creates_a_draft_file_if_none_exists_then_runs_trace_command()
    {
        $file = \Mockery::mock(Filesystem::class);

        $file->shouldReceive('exists')
        ->andReturn(false);

        $file->expects('stub')
        ->with('draft.stub')
        ->andReturn('stub');

        $file->expects('put')
        ->with('draft.yaml', 'stub');

        $trace = \Mockery::mock(TraceCommand::class);
        $trace->expects('handle');

        (new StartCommand($file))->handle();
    }
}
