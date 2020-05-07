<?php

namespace Tests\Unit;

use Facades\Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
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

        \Mockery::close();
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

        \Mockery::close();
    }

    /**
     * @test
     */
    public function it_always_runs_the_trace_command_after_checking_for_draft_file()
    {
        Artisan::shouldReceive('call')->with('blueprint:trace');

        \Mockery::close();
    }
}
