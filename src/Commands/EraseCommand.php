<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;

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

        $blueprint = resolve(Blueprint::class);

        $generated = $blueprint->parse($contents, false);

        collect($generated)->each(function ($files, $action) {
            if ($action === 'created') {
                $this->line('Deleted:', $this->outputStyle($action));
                $this->files->delete($files);
            } elseif ($action === 'updated') {
                $this->comment('The updates to the following files can not be erased automatically.');
            } else {
                return;
            }

            collect($files)->each(function ($file) {
                $this->line('- '.$file);
            });

            $this->line('');
        });

        unset($generated['created']);
        unset($generated['updated']);

        $this->files->put('.blueprint', $blueprint->dump($generated));

        $this->call('blueprint:trace');
    }

    private function outputStyle($action)
    {
        if ($action === 'created') {
            return 'error';
        }

        return 'comment';
    }
}
