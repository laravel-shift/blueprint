<?php

namespace Blueprint\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class StartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create example draft.yaml file in project root';

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
        if ($this->files->exists('draft.yaml')) {
            $this->info('Draft file already exists');
        }

        if(!$this->files->exists('draft.yaml')) {
            $stub = $this->files->stub('draft.stub');
    
            $this->files->put('draft.yaml', $stub);
        
            $this->info('Created example draft.yaml file in project root');
        }

        $this->call('blueprint:trace');
    }
}
