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
                            {--expand : Rewrite shorthand into standard YAML before validating }
                            ';

    protected $help = 'Validates the draft by running the build without writing any files. Errors mean the build will fail. Warnings mean the build will succeed, but generate invalid code or ignore part of the draft.

Blueprint also accepts shorthand which is not standard YAML, such as a bare <comment>softDeletes</comment> or <comment>resource</comment> line, or repeated statements within an action. Editors using the Blueprint JSON Schema will report these lines as errors, even though the draft validates. Use the <comment>--expand</comment> option to rewrite them into their explicit, standard YAML form.';

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

        $blueprint = resolve(Blueprint::class);

        if ($this->option('expand')) {
            $contents = str_replace(["\r\n", "\r"], "\n", $this->filesystem->get($file));
            $expanded = $blueprint->expand($contents);

            if ($expanded !== $contents) {
                $this->filesystem->put($file, $expanded);
                $this->line($file . ': Expanded shorthand into standard YAML.');
            }
        }

        $diagnostics = $this->validator->validate($blueprint, $file);

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
