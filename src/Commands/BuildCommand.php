<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;

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
                            {--auto-skip : Automatically skip files that already exist }
                            {--m|overwrite-migrations : Update existing migration files, if found }
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build components from a Blueprint draft';

    protected Filesystem $filesystem;

    private Builder $builder;

    public function __construct(Filesystem $filesystem, Builder $builder)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->builder = $builder;
    }

    public function handle(): int
    {
        $file = $this->argument('draft') ?? $this->defaultDraftFile();

        if (!$this->filesystem->exists($file)) {
            $this->error('Draft file could not be found: ' . ($file ?: 'draft.yaml'));

            return 1;
        }
        $only = $this->option('only') ?: '';
        $skip = $this->option('skip') ?: '';
        $overwriteMigrations = $this->option('overwrite-migrations') ?: false;
        $autoSkip = $this->option('auto-skip') ?: false;

        if ($autoSkip) {
            $overwriteMigrations = true;
            $blueprintFile = $this->filesystem->path('.blueprint');
            if ($this->filesystem->exists($blueprintFile)) {
                $blueprintContent = $this->filesystem->get($blueprintFile);
                $blueprintContent = Yaml::parse($blueprintContent);
                $blueprintClasses = $blueprintContent['created'] ?? [];
                $skip = implode(',', array_map(function($class) {
                    return pathinfo($class, PATHINFO_FILENAME);
                }, explode(',', $blueprintClasses)));
            }
        }

        $blueprint = resolve(Blueprint::class);
        $generated = $this->builder->execute($blueprint, $this->filesystem, $file, $only, $skip, $overwriteMigrations);

        collect($generated)->each(
            function ($files, $action) {
                $this->line(Str::studly($action) . ':', $this->outputStyle($action));
                collect($files)->each(
                    function ($file) {
                        $this->line('- ' . $file);
                    }
                );

                $this->line('');
            }
        );

        return 0;
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['draft', InputArgument::OPTIONAL, 'The path to the draft file, default: draft.yaml or draft.yml', []],
        ];
    }

    private function outputStyle(string $action): string
    {
        if ($action === 'deleted') {
            return 'error';
        } elseif ($action === 'updated' || $action === 'skipped') {
            return 'comment';
        }

        return 'info';
    }

    private function defaultDraftFile(): string
    {
        return file_exists('draft.yml') ? 'draft.yml' : 'draft.yaml';
    }
}
