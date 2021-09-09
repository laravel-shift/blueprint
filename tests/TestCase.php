<?php

namespace Tests;

use Blueprint\BlueprintServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /** @var Filesystem */
    protected $filesystem;

    /** @var Filesystem */
    protected $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = $this->filesystem = File::spy();
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
        $app['config']->set('database.default', 'testing');
    }

    public function fixture(string $path)
    {
        return file_get_contents(__DIR__ . '/' . 'fixtures' . '/' . ltrim($path, '/'));
    }

    public function requireFixture(string $path)
    {
        require_once __DIR__ . '/' . 'fixtures' . '/' . ltrim($path, '/');
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
}
