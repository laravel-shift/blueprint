<?php

namespace Tests\Unit;

use Blueprint\Commands\StartCommand;
use Blueprint\Commands\TraceCommand;
use Facades\Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

class StartCommandTest extends TestCase
{

    /**
     * @test
     */
    public function it_creates_a_draft_file_if_none_exists_then_runs_trace_command()
    {
        Filesystem::shouldReceive('exists')
        ->andReturn(false);

        Filesystem::expects('stub')
        ->with('draft.stub')
        ->andReturn('stub');

        Filesystem::expects('put')
        ->with('draft.yaml', 'stub');

        // $this->artisan('blueprint:start');
        resolve(StartCommand::class)->handle();
    }
}
