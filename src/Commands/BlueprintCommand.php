<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
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

        $blueprint = resolve(Blueprint::class);
        $generated = Builder::execute($blueprint, $this->files, $file);

        collect($generated)->each(function ($files, $action) {
            $this->line(Str::studly($action) . ':', $this->outputStyle($action));
            collect($files)->each(function ($file) {
                $this->line('- ' . $file);
            });

            $this->line('');
        });
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
