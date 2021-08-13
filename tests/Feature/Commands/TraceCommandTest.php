<?php

namespace Tests\Feature\Commands;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Blueprint\Commands\TraceCommand;
use Blueprint\Tracer;
use Illuminate\Support\Facades\File;
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
        $tracer = $this->mock(Tracer::class);

        $tracer->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files, [])
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
            ->with(resolve(Blueprint::class), $this->files, [])
            ->andReturn([
                "Model" => [],
                "OtherModel" => [],
            ]);

        $this->artisan('blueprint:trace')
            ->assertExitCode(0)
            ->expectsOutput('Traced 2 models');
    }

    /** @test */
    public function relative_class_name_removes_models_namespace()
    {
        $this->requireFixture('models/comment.php');
        $this->requireFixture('models/custom-models-namespace.php');

        $method = new \ReflectionMethod(Tracer::class, 'relativeClassName');
        $method->setAccessible(true);

        // App namespace
        config(['blueprint.models_namespace' => '']);

        $this->assertEquals($method->invoke(new Tracer(), app('App\Comment')), 'Comment');
        $this->assertEquals($method->invoke(new Tracer(), app('App\Models\Tag')), 'Models\Tag');

        // Models namespace
        config(['blueprint.models_namespace' => 'Models']);

        $this->assertEquals($method->invoke(new Tracer(), app('App\Comment')), 'Comment');
        $this->assertEquals($method->invoke(new Tracer(), app('App\Models\Tag')), 'Tag');
    }
  
    public function it_passes_the_command_path_to_tracer()
    {
        $this->filesystem->shouldReceive('exists')
            ->with('test.yml')
            ->andReturnTrue();

        $builder = $this->mock(Builder::class);

        $builder->shouldReceive('execute')
            ->with(resolve(Blueprint::class), $this->files, 'vendor/package/src/app/Models')
            ->andReturn([]);

        $this->artisan('blueprint:trace --path=vendor/package/src/app/Models')
            ->assertExitCode(0);
    }
}
