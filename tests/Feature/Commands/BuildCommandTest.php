<?php

namespace Tests\Feature\Commands;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;
use Tests\Traits\MocksFilesystem;

/**
 * @covers \Blueprint\Commands\BuildCommand
 */
class BuildCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration, MocksFilesystem;

    /** @test */
    public function it_uses_the_default_draft_file()
    {
        $this->files->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnTrue();

        $builder = $this->mock(Builder::class);

        $builder->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files, 'draft.yaml', '', '', false)
            ->andReturn(collect([]));

        $this->artisan('blueprint:build')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_passes_the_command_args_to_the_builder_in_right_order()
    {
        $this->files->shouldReceive('exists')
            ->with('test.yml')
            ->andReturnTrue();

        $builder = $this->mock(Builder::class);

        $builder->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files, 'test.yml', 'a,b,c', 'x,y,z', false)
            ->andReturn(collect([]));

        $this->artisan('blueprint:build test.yml --only=a,b,c --skip=x,y,z')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_fails_if_the_draft_file_not_exists()
    {
        $this->files->shouldReceive('exists')
            ->with('test.yml')
            ->andReturnFalse();

        $builder = $this->mock(Builder::class);

        $builder->shouldNotReceive('execute');

        $this->artisan('blueprint:build test.yml --only=a,b,c --skip=x,y,z')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_shows_the_generated_files_groupbed_by_actions()
    {
        $this->files->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnTrue();
        $builder = $this->mock(Builder::class);
        $builder->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files, 'draft.yaml', '', '', false)
            ->andReturn(collect([
                "created" => [
                    "file1",
                    "file2",
                ]
            ]));
        $this->artisan('blueprint:build')
            ->assertExitCode(0)
            ->expectsOutput('Created:')
            ->expectsOutput('- file1')
            ->expectsOutput('- file2');
    }
}
