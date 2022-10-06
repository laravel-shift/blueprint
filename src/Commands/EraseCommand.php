<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

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

    /** @var Filesystem */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    public function handle()
    {
        $contents = $this->filesystem->get('.blueprint');

        $blueprint = resolve(Blueprint::class);

        $generated = $blueprint->parse($contents, false);

        collect($generated)->each(
            function ($files, $action) {
                if ($action === 'created') {
                    $this->line('Deleted:', $this->outputStyle($action));
                    $this->filesystem->delete($files);
                } elseif ($action === 'updated') {
                    $this->comment('The updates to the following files can not be erased automatically.');
                } else {
                    return;
                }

                collect($files)->each(
                    function ($file) {
                        $this->line('- ' . $file);
                    }
                );

                $this->line('');
            }
        );

        unset($generated['created']);
        unset($generated['updated']);

        $this->filesystem->put('.blueprint', $blueprint->dump($generated));

        return $this->call('blueprint:trace');
    }

    private function outputStyle($action)
    {
        if ($action === 'created') {
            return 'error';
        }

        return 'comment';
    }
}
