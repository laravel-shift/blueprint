<?php

namespace Blueprint;

use Blueprint\Commands\BuildCommand;
use Blueprint\Commands\EraseCommand;
use Blueprint\Commands\NewCommand;
use Blueprint\Commands\TraceCommand;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\File;
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

        if (!defined('CUSTOM_STUBS_PATH')) {
            define('CUSTOM_STUBS_PATH', base_path('stubs/blueprint'));
        }

        $this->publishes([
            __DIR__ . '/../config/blueprint.php' => config_path('blueprint.php'),
        ], 'blueprint-config');

        $this->publishes([
            dirname(__DIR__) . '/stubs' => CUSTOM_STUBS_PATH,
        ], 'blueprint-stubs');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/blueprint.php',
            'blueprint'
        );

        File::mixin(new FileMixins());

        $this->app->bind(
            'command.blueprint.build',
            function ($app) {
                return new BuildCommand($app['files'], app(Builder::class));
            }
        );
        $this->app->bind(
            'command.blueprint.erase',
            function ($app) {
                return new EraseCommand($app['files']);
            }
        );
        $this->app->bind(
            'command.blueprint.trace',
            function ($app) {
                return new TraceCommand($app['files'], app(Tracer::class));
            }
        );
        $this->app->bind(
            'command.blueprint.new',
            function ($app) {
                return new NewCommand($app['files']);
            }
        );

        $this->app->singleton(Blueprint::class, function ($app) {
            $blueprint = new Blueprint();
            $blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
            $blueprint->registerLexer(new \Blueprint\Lexers\SeederLexer());
            $blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new \Blueprint\Lexers\StatementLexer()));

            $blueprint->registerGenerator(new \Blueprint\Generators\MigrationGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\ModelGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\FactoryGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\SeederGenerator($app['files']));

            $blueprint->registerGenerator(new \Blueprint\Generators\ControllerGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\EventGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\FormRequestGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\ResourceGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\JobGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\MailGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\NotificationGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\Statements\ViewGenerator($app['files']));
            $blueprint->registerGenerator(new \Blueprint\Generators\RouteGenerator($app['files']));

            $blueprint->registerGenerator(new \Blueprint\Generators\TestGenerator($app['files']));

            return $blueprint;
        });

        $this->commands([
            'command.blueprint.build',
            'command.blueprint.erase',
            'command.blueprint.trace',
            'command.blueprint.new',
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
            'command.blueprint.trace',
            'command.blueprint.new',
            Blueprint::class,
        ];
    }
}
