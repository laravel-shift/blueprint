<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
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
    protected $description = 'Erase components created from last Blueprint build';

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
        $contents = $this->files->get('.blueprint');

        $blueprint = new Blueprint();
        $generated = $blueprint->parse($contents);

        collect($generated)->each(function ($files, $action) {
            if ($action === 'created') {
                $this->line('Deleted:', $this->outputStyle($action));
                $this->files->delete($files);
            } elseif ($action === 'updated') {
                $this->comment('The updates to the following files can not be erased automatically.');
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
