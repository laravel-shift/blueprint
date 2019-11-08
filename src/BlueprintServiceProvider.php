<?php

namespace Blueprint;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Blueprint\Generators\ModelGenerator;
use Blueprint\Generators\FactoryGenerator;
use Blueprint\Generators\MigrationGenerator;
use Illuminate\Contracts\Support\DeferrableProvider;

class BlueprintServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot(Blueprint $blueprint)
    {
        $this->publishes([
            __DIR__ . '/../config/blueprint.php' => config_path('blueprint.php'),
        ], 'config');

        if (! defined('STUBS_PATH')) {
            define('STUBS_PATH', dirname(__DIR__) . '/stubs');
        }

        $blueprint->boot();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/blueprint.php', 'blueprint');

        $this->app->bind('blueprint.generators', function ($app) {
            return [
                FactoryGenerator::class => new FactoryGenerator($app->make(Filesystem::class)),
                MigrationGenerator::class => new MigrationGenerator($app->make(Filesystem::class)),
                ModelGenerator::class => new ModelGenerator($app->make(Filesystem::class)),
            ];
        });

        $this->app->singleton(Blueprint::class, Blueprint::class);

        $this->app->bind(
            'command.blueprint.build',
            function ($app) {
                return new BlueprintCommand($app['files']);
            }
        );

        $this->commands('command.blueprint.build');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.blueprint.build'];
    }
}
