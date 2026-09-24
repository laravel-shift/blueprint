<?php

namespace Blueprint\Commands;

use Blueprint\Blueprint;
use Blueprint\Validator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class ValidateCommand extends Command
{
    protected $description = 'Validate a Blueprint draft builds';

    protected $signature = 'blueprint:validate
                            {draft? : The path to the draft file, default: draft.yaml or draft.yml }
                            ';

    protected Filesystem $filesystem;

    private Validator $validator;

    public function __construct(Filesystem $filesystem, Validator $validator)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->validator = $validator;
    }

    public function handle(): int
    {
        $file = $this->argument('draft') ?? $this->defaultDraftFile();

        if (!$this->filesystem->exists($file)) {
            $this->line($file . ': error: Draft file could not be found.');

            return 1;
        }

        $diagnostics = $this->validator->validate(resolve(Blueprint::class), $file);

        foreach ($diagnostics as $diagnostic) {
            $this->line(sprintf(
                '%s%s: %s: %s',
                $file,
                $diagnostic['line'] ? ':' . $diagnostic['line'] : '',
                $diagnostic['severity'],
                $diagnostic['message']
            ));
        }

        if (empty($diagnostics)) {
            $this->line($file . ': Draft is valid.');
        }

        return collect($diagnostics)->contains('severity', 'error') ? 1 : 0;
    }

    private function defaultDraftFile(): string
    {
        return file_exists('draft.yml') ? 'draft.yml' : 'draft.yaml';
    }
}
