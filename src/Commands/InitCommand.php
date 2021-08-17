<?php

namespace Blueprint\Commands;

use Illuminate\Console\Command;

class InitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'An alias for "blueprint:new" command';

    public function handle()
    {
        return $this->call(\Blueprint\Commands\NewCommand::class);
    }
}
