<?php

namespace Tests\Feature\Commands;

use Blueprint\Blueprint;
use Blueprint\Tracer;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

use function resolve;

/**
 * @covers \Blueprint\Commands\TraceCommand
 */
class TraceCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @test */
    public function it_shows_error_if_no_model_found()
    {
        $tracer = $this->mock(Tracer::class);

        $tracer->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files)
            ->andReturn([]);

        $this->artisan('blueprint:trace')
            ->assertExitCode(0)
            ->expectsOutput('No models found');
    }

    /** @test */
    public function it_shows_the_number_of_traced_models()
    {
        $tracer = $this->mock(Tracer::class);

        $tracer->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files)
            ->andReturn([
                "Model"      => [],
                "OtherModel" => [],
            ]);

        $this->artisan('blueprint:trace')
            ->assertExitCode(0)
            ->expectsOutput('Traced 2 models');
    }
}
