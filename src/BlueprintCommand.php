<?php

namespace Blueprint;

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
     * @param \App\DripEmailer $drip
     * @return mixed
     */
    public function handle()
    {
        $contents = $this->files->get($this->argument('draft'));

        $blueprint = new Blueprint(); // TODO: resolve or make static for extensibility

        $blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());

        $blueprint->registerGenerator(new \Blueprint\Generators\MigrationGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\ModelGenerator($this->files));
        $blueprint->registerGenerator(new \Blueprint\Generators\FactoryGenerator($this->files));

        $tokens = $blueprint->parse($contents);
        $registry = $blueprint->analyze($tokens);
        $blueprint->generate($registry);
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
}