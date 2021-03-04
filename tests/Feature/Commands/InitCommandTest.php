<?php

namespace Tests\Feature\Commands;

use Blueprint\Commands\NewCommand;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;
use Tests\Traits\MocksFilesystem;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @covers \Blueprint\Commands\InitCommand
 */
class InitCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use MocksFilesystem;


    /**
     * @test
     */
    public function it_creates_a_draft_file_from_stub_if_none_exists()
    {
        $this->files->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnFalse();
        $this->files->shouldReceive('stub')
            ->with('draft.stub')
            ->andReturn('stub');
        $this->files->shouldReceive('put')
            ->with('draft.yaml', 'stub');

        $this->files->shouldReceive('exists')->with('app');

        $this->artisan('blueprint:init')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_does_not_create_a_draft_file_if_one_exists_already()
    {
        $this->files->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnTrue();
        $this->files->shouldNotReceive('put');
        $this->files->shouldReceive('exists')
            ->with('app');

        $this->artisan('blueprint:init')
            ->assertExitCode(0);
    }
}
