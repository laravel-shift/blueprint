<?php

namespace Tests\Feature\Commands;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

/**
 * @covers \Blueprint\Commands\EraseCommand
 */
class EraseCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @test */
    public function it_parses_and_update_the_trace_file()
    {
        $filesystem = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $this->swap('files', $filesystem);

        $filesystem->expects('get')
            ->with('.blueprint')
            ->andReturn("created: created_file.php \nupdated: updated_file.php \nother: test.php");

        $filesystem->expects('put')
            ->with('.blueprint', "other: test.php\n");

        $this->artisan('blueprint:erase')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_deletes_the_created_files()
    {
        $filesystem = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $this->swap('files', $filesystem);

        $filesystem->expects('get')
            ->with('.blueprint')
            ->andReturn("created:\n  -  created_file1.php\n  -  created_file2.php");

        $filesystem->expects('delete')->with([
            "created_file1.php",
            "created_file2.php",
        ]);

        $this->artisan('blueprint:erase')
            ->assertExitCode(0)
            ->expectsOutput("Deleted:")
            ->expectsOutput("- created_file1.php")
            ->expectsOutput("- created_file2.php");
    }

    /** @test */
    public function it_notify_about_the_updated_files()
    {
        $filesystem = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $this->swap('files', $filesystem);

        $filesystem->expects('get')
            ->with('.blueprint')
            ->andReturn("updated:\n  -  updated_file1.php\n  -  updated_file2.php");

        $this->artisan('blueprint:erase')
            ->assertExitCode(0)
            ->expectsOutput("The updates to the following files can not be erased automatically.")
            ->expectsOutput("- updated_file1.php")
            ->expectsOutput("- updated_file2.php");
    }

    /** @test */
    public function it_calls_the_trace_command()
    {
        $filesystem = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $this->swap('files', $filesystem);

        $filesystem->expects('get')->with('.blueprint')->andReturn("other: test.php");
        $filesystem->expects('put')->with('.blueprint', "other: test.php\n");

        $this->artisan('blueprint:erase')
            ->assertExitCode(0);
    }
}
