<?php

namespace Blueprint;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class EraseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:erase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Erase components created from a Blueprint draft';

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
        $contents = $this->files->get('.last_build.yaml');

        $blueprint = new Blueprint();
        $lastBuild = $blueprint->parse($contents);

        collect($lastBuild)->each(function ($files, $action) {
            if ($action === 'created') {
                $this->files->delete($files);
            }

            $this->line(Str::studly($action) . ':', $this->outputStyle($action));

            if ($action === 'updated') {
                $this->error(
                    'Please check the following files which cannot be erased of previous changes automatically.',
                );
            }

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
        if ($action === 'created') {
            return 'error';
        }

        return 'comment';
    }
}
