<?php

namespace Tests;

use Blueprint\BlueprintServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
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

    public function fixture(string $path)
    {
        return file_get_contents(__DIR__ . '/' . 'fixtures' . '/' . ltrim($path, '/'));
    }

    public function stub(string $path)
    {
        return file_get_contents(__DIR__ . '/../' . 'stubs' . '/' . ltrim($path, '/'));
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
}
