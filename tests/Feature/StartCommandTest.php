<?php

namespace Tests\Unit;

use Blueprint\Commands\StartCommand;
use Blueprint\Commands\TraceCommand;
use Facades\Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class StartCommandTest extends TestCase
{

    /**
     * @test
     */
    public function it_creates_a_draft_file_from_stub_if_none_exists()
    {
        Filesystem::shouldReceive('exists')
        ->andReturn(false);

        Filesystem::shouldReceive('stub')
        ->with('draft.stub')
        ->andReturn('stub');

        Filesystem::shouldReceive('put')
        ->with('draft.yaml', 'stub');

        Artisan::call('blueprint:start');
    }

    /**
     * @test
     */
    public function it_does_not_create_a_draft_file_if_one_exists_already()
    {
        Filesystem::shouldReceive('exists')
        ->andReturn(true);

        Filesystem::shouldNotReceive('put');

        Artisan::call('blueprint:start');
    }

    /**
     * @test
     */
    // public function it_runs_the_trace_command_
}
