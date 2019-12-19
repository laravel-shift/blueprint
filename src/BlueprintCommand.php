<?php

namespace Blueprint;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class BlueprintCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:build {draft=draft.yaml}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build components from a Blueprint draft';

    /** @var Filesystem $files */
    protected $files;

    /**
     * @param Filesystem $files
     * @param \Illuminate\Contracts\View\Factory $view
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = $this->argument('draft');
        if (!file_exists($file)) {
            $this->error('Draft file could not be found: ' . $file);
        }

        $contents = $this->files->get($file);

        $blueprint = new Blueprint();

        $blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new \Blueprint\Lexers\StatementLexer()));

        $blueprint->registerGenerator(new \Blueprint\Generators\MigrationGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\ModelGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\FactoryGenerator($this->files));

        $blueprint->registerGenerator(new \Blueprint\Generators\ControllerGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\Statements\EventGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\Statements\FormRequestGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\Statements\JobGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\Statements\MailGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\Statements\ViewGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\RouteGenerator($this->files));

        $tokens = $blueprint->parse($contents);
        $registry = $blueprint->analyze($tokens);
        $generated = $blueprint->generate($registry);

        collect($generated)->each(function ($files, $action) {
            $this->line(Str::studly($action) . ':', $this->outputStyle($action));
            collect($files)->each(function ($file) {
                $this->line('- ' . $file);
            });

            $this->line('');
        });

        $this->files->put(
            '.blueprint',
            $blueprint->dump($generated)
        );
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['draft', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Which models to include', []],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    private function outputStyle($action)
    {
        if ($action === 'deleted') {
            return 'error';
        } elseif ($action === 'updated') {
            return 'comment';
        }

        return 'info';
    }
}
