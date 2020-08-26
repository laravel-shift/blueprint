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
                            {draft? : The path to the draft file, default: draft.yaml or draft.yml }
                            {--only= : Comma separated list of file classes to generate, skipping the rest }
                            {--skip= : Comma separated list of file classes to skip, generating the rest }
                            {--m|overwrite-migrations : Update existing migration files, if found }
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build components from a Blueprint draft';

    /** @var Filesystem */
    protected $files;

    /** @var Builder */
    private $builder;

    /**
     * @param Filesystem $files
     * @param Builder $builder
     */
    public function __construct(Filesystem $files, Builder $builder)
    {
        parent::__construct();

        $this->files = $files;
        $this->builder = $builder;
    }

    public function handle()
    {
        $file = $this->argument('draft') ?? $this->defaultDraftFile();

        if (!$this->files->exists($file)) {
            $this->error('Draft file could not be found: '.($file ?: 'draft.yaml'));
            return 1;
        }

        $only = $this->option('only') ?: '';
        $skip = $this->option('skip') ?: '';
        $overwriteMigrations = $this->option('overwrite-migrations') ?: false;

        $blueprint = resolve(Blueprint::class);
        $generated = $this->builder->execute($blueprint, $this->files, $file, $only, $skip, $overwriteMigrations);

        collect($generated)->each(function ($files, $action) {
            $this->line(Str::studly($action).':', $this->outputStyle($action));
            collect($files)->each(function ($file) {
                $this->line('- '.$file);
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
        return file_exists('draft.yml') ? 'draft.yml' : 'draft.yaml';
    }
}
