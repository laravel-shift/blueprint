<?php

namespace Tests\Feature\Commands;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Blueprint\Commands\TraceCommand;
use Blueprint\Tracer;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

/**
 * @covers \Blueprint\Commands\TraceCommand
 */
class TraceCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @test */
    public function it_shows_error_if_no_model_found()
    {
        $filesystem = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $this->swap('files', $filesystem);

        $tracer = $this->mock(Tracer::class);

        $tracer->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $filesystem)
            ->andReturn([]);

        $this->artisan('blueprint:trace')
            ->assertExitCode(0)
            ->expectsOutput('No models found');
    }

    /** @test */
    public function it_shows_the_number_of_traced_models()
    {
        $filesystem = \Mockery::mock(\Illuminate\Filesystem\Filesystem::class)->makePartial();
        $this->swap('files', $filesystem);

        $tracer = $this->mock(Tracer::class);

        $tracer->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $filesystem)
            ->andReturn([
                "Model" => [],
                "OtherModel" => [],
            ]);

        $this->artisan('blueprint:trace')
            ->assertExitCode(0)
            ->expectsOutput('Traced 2 models');
    }
}
