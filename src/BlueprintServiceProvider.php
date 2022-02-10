<?php

namespace Blueprint;

use Blueprint\Commands\BuildCommand;
use Blueprint\Commands\EraseCommand;
use Blueprint\Commands\InitCommand;
use Blueprint\Commands\NewCommand;
use Blueprint\Commands\PublishStubsCommand;
use Blueprint\Commands\TraceCommand;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Contracts\Console\Kernel;
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

        $this->app->bind('command.blueprint.build', fn ($app) => new BuildCommand($app['files'], app(Builder::class)));
        $this->app->bind('command.blueprint.erase', fn ($app) => new EraseCommand($app['files']));
        $this->app->bind('command.blueprint.trace', fn ($app) => new TraceCommand($app['files'], app(Tracer::class)));
        $this->app->bind('command.blueprint.new', fn ($app) => new NewCommand($app['files']));
        $this->app->bind('command.blueprint.init', fn ($app) => new InitCommand());
        $this->app->bind('command.blueprint.stubs', fn ($app) => new PublishStubsCommand());

        $this->app->singleton(Blueprint::class, function ($app) {
            $blueprint = new Blueprint();
            $blueprint->registerLexer(new \Blueprint\Lexers\ConfigLexer($app));
            $blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
            $blueprint->registerLexer(new \Blueprint\Lexers\SeederLexer());
            $blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new \Blueprint\Lexers\StatementLexer()));

            foreach (config('blueprint.generators') as $generator) {
                $blueprint->registerGenerator(new $generator($app['files']));
            }

            return $blueprint;
        });

        $this->app->make('events')->listen(CommandFinished::class, function ($event) {
            if ($event->command == 'stub:publish') {
                $this->app->make(Kernel::class)->queue('blueprint:stubs');
            }
        });

        $this->commands([
            'command.blueprint.build',
            'command.blueprint.erase',
            'command.blueprint.trace',
            'command.blueprint.new',
            'command.blueprint.init',
            'command.blueprint.stubs',
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
            'command.blueprint.init',
            Blueprint::class,
        ];
    }
}
