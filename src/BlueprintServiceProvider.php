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

        $this->publishes([
            __DIR__.'/../config/blueprint.php' => config_path('blueprint.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/blueprint.php', 'blueprint'
        );

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

        $this->app->singleton(Blueprint::class, function ($app) {
            $blueprint = new Blueprint();
            $blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
            $blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new \Blueprint\Lexers\StatementLexer()));

            $blueprint->registerGenerator(new \Blueprint\Generators\MigrationGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\ModelGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\FactoryGenerator($app['files']));

            $blueprint->registerGenerator(new \Blueprint\Generators\ControllerGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\EventGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\FormRequestGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\JobGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\MailGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\ViewGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\RouteGenerator($app['files']));

            return $blueprint;
        });

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
            Blueprint::class,
        ];
    }
}
