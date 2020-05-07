<?php

namespace Blueprint\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class NewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a draft.yaml file and load existing models';

    /** @var Filesystem $files */
    protected $files;

    /**
     * @param Filesystem $files
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
        if (!$this->files->exists('draft.yaml')) {
            $this->files->put('draft.yaml', $this->files->stub('draft.stub'));

            $this->info('Created example draft.yaml');
        }

        $this->call('blueprint:trace');
    }
}
