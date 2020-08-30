<?php

namespace Tests\Feature\Commands;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;
use Tests\Traits\MocksFilesystem;

/**
 * @covers \Blueprint\Commands\NewCommand
 */
class NewCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration, MocksFilesystem;

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

        $this->artisan('blueprint:new')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_does_not_create_a_draft_file_if_one_exists_already()
    {
        $this->files->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnTrue();
        $this->files->shouldNotReceive('put');
        $this->files->shouldReceive('exists')
            ->with('app');

        $this->artisan('blueprint:new')
            ->assertExitCode(0);
    }
}
