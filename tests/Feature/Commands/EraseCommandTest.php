<?php

namespace Tests\Feature\Commands;

use Blueprint\Blueprint;
use Blueprint\Tracer;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see \Blueprint\Commands\EraseCommand
 */
final class EraseCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    public function it_parses_and_update_the_trace_file(): void
    {
        $this->filesystem->expects('get')
            ->with('.blueprint')
            ->andReturn("created: created_file.php \nupdated: updated_file.php \nother: test.php");

        $this->filesystem->expects('delete')->with('created_file.php');

        $this->filesystem->expects('put')
            ->with('.blueprint', "other: test.php\n");

        $this->filesystem->expects('exists')->with('app/Models');

        $this->artisan('blueprint:erase')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_deletes_the_created_files(): void
    {
        $this->filesystem->expects('get')
            ->with('.blueprint')
            ->andReturn("created:\n  -  created_file1.php\n  -  created_file2.php");

        $this->filesystem->expects('delete')->with([
            'created_file1.php',
            'created_file2.php',
        ]);

        $this->filesystem->expects('put')->with('.blueprint', '{  }');
        $this->filesystem->expects('exists')->with('app/Models');

        $this->artisan('blueprint:erase')
            ->assertExitCode(0)
            ->expectsOutput('Deleted:')
            ->expectsOutput('- created_file1.php')
            ->expectsOutput('- created_file2.php');
    }

    #[Test]
    public function it_notify_about_the_updated_files(): void
    {
        $this->filesystem->expects('get')
            ->with('.blueprint')
            ->andReturn("updated:\n  -  updated_file1.php\n  -  updated_file2.php");

        $this->filesystem->expects('put')->with('.blueprint', '{  }');
        $this->filesystem->expects('exists')->with('app/Models');

        $this->artisan('blueprint:erase')
            ->assertExitCode(0)
            ->expectsOutput('The updates to the following files can not be erased automatically.')
            ->expectsOutput('- updated_file1.php')
            ->expectsOutput('- updated_file2.php');
    }

    #[Test]
    public function it_calls_the_trace_command(): void
    {
        $this->filesystem->expects('get')->with('.blueprint')->andReturn('other: test.php');
        $this->filesystem->expects('put')->with('.blueprint', "other: test.php\n");

        $tracer = $this->spy(Tracer::class);

        $this->artisan('blueprint:erase')
            ->assertExitCode(0);

        $tracer->shouldHaveReceived('execute')
            ->with(resolve(Blueprint::class), $this->filesystem, []);
    }
}
