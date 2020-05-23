<?php

namespace Blueprint\Commands;

class RebuildCommand extends BuildCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blueprint:rebuild {draft?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Erases and rebuild components from a Blueprint draft';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('php artisan blueprint:erase');

        parent::handle();
    }
}
