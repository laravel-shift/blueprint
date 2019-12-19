<?php

namespace Blueprint;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class BlueprintServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if (!defined('STUBS_PATH')) {
            define('STUBS_PATH', dirname(__DIR__) . '/stubs');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('command.blueprint.build',
            function ($app) {
                return new BlueprintCommand($app['files']);
            }
        );
        $this->app->bind('command.blueprint.erase',
            function ($app) {
                return new EraseCommand($app['files']);
            }
        );

        $this->commands([
            'command.blueprint.build',
            'command.blueprint.erase',
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.blueprint.build',
            'command.blueprint.erase',
        ];
    }
}
