<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\Artisan;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

/**
 * @covers \Blueprint\Commands\StartCommand;
 */
class StartCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     */
    public function it_creates_a_draft_file_from_stub_if_none_exists()
    {
        $filesystem = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $this->swap('files', $filesystem);

        $filesystem->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnFalse();
        $filesystem->shouldReceive('stub')
            ->with('draft.stub')
            ->andReturn('stub');
        $filesystem->shouldReceive('put')
            ->with('draft.yaml', 'stub');

        $this->artisan('blueprint:start')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_does_not_create_a_draft_file_if_one_exists_already()
    {
        $filesystem = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $this->swap('files', $filesystem);

        $filesystem->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnTrue();
        $filesystem->shouldNotReceive('put');

        $this->artisan('blueprint:start')
            ->assertExitCode(0);
    }
}
