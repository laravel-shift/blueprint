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
        $app['config']->set('blueprint.fake_nullables', true);
    }

    public function fixture(string $path)
    {
        $buffer = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
        return str_replace(["\r\n","\r"], "\n", $buffer);
    }

    public function stubs(string $path)
    {
        $buffer = file_get_contents($path);
        return str_replace(["\r\n","\r"], "\n", $buffer);
    }
}
