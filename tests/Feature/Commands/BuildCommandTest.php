<?php

namespace Tests\Feature\Commands;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \Blueprint\Commands\BuildCommand
 */
final class BuildCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function it_uses_the_default_draft_file(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnTrue();

        $builder = $this->mock(Builder::class);

        $builder->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files, 'draft.yaml', '', '', false)
            ->andReturn([]);

        $this->artisan('blueprint:build')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_passes_the_command_args_to_the_builder_in_right_order(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->with('test.yml')
            ->andReturnTrue();

        $builder = $this->mock(Builder::class);

        $builder->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files, 'test.yml', 'a,b,c', 'x,y,z', false)
            ->andReturn([]);

        $this->artisan('blueprint:build test.yml --only=a,b,c --skip=x,y,z')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_fails_if_the_draft_file_not_exists(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->with('test.yml')
            ->andReturnFalse();

        $builder = $this->mock(Builder::class);

        $builder->shouldNotReceive('execute');

        $this->artisan('blueprint:build test.yml --only=a,b,c --skip=x,y,z')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_shows_the_generated_files_grouped_by_actions(): void
    {
        $this->filesystem->shouldReceive('exists')
            ->with('draft.yaml')
            ->andReturnTrue();
        $builder = $this->mock(Builder::class);
        $builder->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files, 'draft.yaml', '', '', false)
            ->andReturn([
                'created' => [
                    'file1',
                    'file2',
                ],
            ]);
        $this->artisan('blueprint:build')
            ->assertExitCode(0)
            ->expectsOutput('Created:')
            ->expectsOutput('- file1')
            ->expectsOutput('- file2');
    }
}
