<?php

namespace Blueprint\Commands;

use Illuminate\Console\Command;

class PublishStubsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:stubs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish blueprint stubs';

    public function handle()
    {
        return $this->call('vendor:publish', ['--tag' => 'blueprint-stubs']);
    }
}
