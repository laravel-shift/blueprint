<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
use Blueprint\Tracer;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class TraceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:trace
                            {--path=* : List of paths to search in }
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create definitions for existing models to reference in new drafts';

    /** @var Filesystem */
    protected $filesystem;

    /** @var Tracer */
    private $tracer;

    public function __construct(Filesystem $filesystem, Tracer $tracer)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->tracer = $tracer;
    }

    public function handle()
    {
        $blueprint = resolve(Blueprint::class);
        $path = $this->option('path');
        $definitions = $this->tracer->execute($blueprint, $this->filesystem, $path);

        if (empty($definitions)) {
            $this->error('No models found');
        } else {
            $this->info('Traced ' . count($definitions) . ' ' . Str::plural('model', count($definitions)));
        }

        return 0;
    }
}
