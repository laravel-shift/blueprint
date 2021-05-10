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
    protected $filesystem;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->filesystem->exists('draft.yaml')) {
            $this->filesystem->put('draft.yaml', $this->filesystem->stub('draft.stub'));

            $this->info('Created example draft.yaml');
        }

        $this->call('blueprint:trace');
    }
}
