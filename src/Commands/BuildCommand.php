<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class BuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:build 
                            {draft? : The path to the draft file, default: draft.yaml or draft.yaml }
                            ';

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
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $file = $this->argument('draft') ?? $this->defaultDraftFile();

        if (!file_exists($file)) {
            $this->error('Draft file could not be found: ' . ($file ?: 'draft.yaml'));
            exit(1);
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
            ['draft', InputArgument::OPTIONAL, 'The path to the draft file, default: draft.yaml or draft.yaml', []],
        ];
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

    private function defaultDraftFile()
    {
        if (file_exists('draft.yaml')) {
            return 'draft.yaml';
        }

        if (file_exists('draft.yml')) {
            return 'draft.yml';
        }

        return null;
    }
}
