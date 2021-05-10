<?php

namespace Tests\Feature\Commands;

use Blueprint\Commands\NewCommand;
use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @covers \Blueprint\Commands\InitCommand
 */
class InitCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     */
    public function it_creates_a_draft_file_from_stub_if_none_exists()
    {
        $this->filesystem->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnFalse();
        $this->filesystem->shouldReceive('stub')
            ->with('draft.stub')
            ->andReturn('stub');
        $this->filesystem->shouldReceive('put')
            ->with('draft.yaml', 'stub');

        $this->filesystem->shouldReceive('exists')->with('app');

        $this->artisan('blueprint:init')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_does_not_create_a_draft_file_if_one_exists_already()
    {
        $this->filesystem->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnTrue();
        $this->filesystem->shouldNotReceive('put');
        $this->filesystem->shouldReceive('exists')
            ->with('app');

        $this->artisan('blueprint:init')
            ->assertExitCode(0);
    }
}
