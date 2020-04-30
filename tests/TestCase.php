<?php

namespace Tests;

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
}
