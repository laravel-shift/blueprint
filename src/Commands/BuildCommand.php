<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
use Blueprint\Builder;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

use function Termwind\render;
use function Termwind\renderUsing;

class BuildCommand extends Command
{
    private Builder $builder;

    protected $description = 'Build components from a Blueprint draft';

    protected Filesystem $filesystem;

    protected $signature = 'blueprint:build
                            {draft? : The path to the draft file, default: draft.yaml or draft.yml }
                            {--only= : Comma separated list of file classes to generate, skipping the rest }
                            {--skip= : Comma separated list of file classes to skip, generating the rest }
                            {--m|overwrite-migrations : Update existing migration files, if found }
                            ';

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

        $blueprint = resolve(Blueprint::class);
        $generated = $this->builder->execute($blueprint, $this->filesystem, $file, $only, $skip, $overwriteMigrations);

        collect($generated)->each(function ($classes, $action) {
            collect($classes)->each(function ($class) use ($action) {
                $this->termwindOutput($action, $class[0], $class[1]);
            });
        });

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

    private function defaultDraftFile(): string
    {
        return file_exists('draft.yml') ? 'draft.yml' : 'draft.yaml';
    }

    private function exampleLine(string $action, string $type, string $path): string
    {
        [$bg, $fg] = match ($action) {
            'created' => ['blue', 'white'],
            'updated' => ['yellow', 'black'],
            'deleted', 'skipped' => ['red', 'white'],
        };

        $go = match ($action) {
            'skipped' => 'not built',
            'updated' => 'appended successfully',
            'deleted' => 'removed successfully',
            default => 'built successfully',
        };

        $title = ucfirst($action);

        $message = sprintf(
            '%s <b class="font-bold">[%s]</b> %s.',
            $type,
            htmlspecialchars($path),
            $go
        );

        $margin = max(0, 2 - $this->output->newLinesWritten());

        return <<<EOT
<div class="mx-2 mb-1 mt-$margin">
    <span class="px-1 bg-$bg text-$fg uppercase">$title</span>
    <span class="ml-1">$message</span>
</div>
EOT;
    }

    private function termwindOutput(string $action, string $type, string $path): void
    {
        renderUsing($this->output);

        render($this->exampleLine($action, $type, $path));
    }
}
