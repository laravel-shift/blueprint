<?php

namespace Tests;

use Blueprint\BlueprintServiceProvider;
use Illuminate\Support\Facades\File;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public static function tearDownAfterClass(): void
    {
        File::cleanDirectory(base_path('stubs'));
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('blueprint.namespace', 'App');
        $app['config']->set('blueprint.controllers_namespace', 'Http\\Controllers');
        $app['config']->set('blueprint.models_namespace', '');
        $app['config']->set('blueprint.app_path', 'app');
        $app['config']->set('blueprint.generate_phpdocs', false);
        $app['config']->set('blueprint.use_constraints', false);
        $app['config']->set('blueprint.fake_nullables', true);
    }

    protected function fixture(string $path)
    {
        return File::get(realpath('./tests/fixtures/'. ltrim($path, '/')));
    }

    protected function stub(string $name)
    {
        $filename = ltrim($name, '/');

        $directory = collect([
            base_path('stubs'),

            base_path('./../../../../stubs'), // TODO: remove, used for Testing v1 stubs dir

            base_path('./../../../../vendor/laravel/framework/src/Illuminate/Foundation/Console/stubs'),
            base_path('./../../../../vendor/laravel/framework/src/Illuminate/Routing/Console/stubs'),
            base_path('./../../../../vendor/laravel/framework/src/Illuminate/Database/Migrations/stubs'),
            base_path('./../../../../vendor/laravel/framework/src/Illuminate/Database/Console/Factories/stubs'),
            base_path('./../../../../vendor/laravel/framework/src/Illuminate/Database/Console/Seeds/stubs'),

        ])->first(function ($directory) use ($filename) {
            return File::isFile($directory .'/'. $filename);
        });

        return File::get($directory .'/'. $filename);
    }

    protected function getPackageProviders($app)
    {
        return [
            BlueprintServiceProvider::class,
        ];
    }

    protected function useLaravel6($app)
    {
        $appMock = \Mockery::mock($app);
        $appMock->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');

        \App::swap($appMock);
    }

    protected function useLaravel7($app)
    {
        $appMock = \Mockery::mock($app);
        $appMock->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('7.0.0');

        \App::swap($appMock);
    }

    protected function useLaravel8($app)
    {
        $appMock = \Mockery::mock($app);
        $appMock->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('8.0.0');

        \App::swap($appMock);
    }
}
