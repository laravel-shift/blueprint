<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Assert as PHPUnit;

class ArtisanFake implements KernelContract
{
    /**
     * The original kernel implementation.
     *
     * @var \Illuminate\Foundation\Console\Kernel
     */
    protected $kernel;

    /**
     * The commands that should be intercepted instead of dispatched.
     *
     * @var array
     */
    protected $commandsToFake;

    /**
     * The commands that have been dispatched.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Create a new artisan fake instance.
     * @param  \Illuminate\Foundation\Console\Kernel  $kernel
     * @param  array|string  $commandsToFake
     * @return void
     */
    public function __construct(Kernel $kernel, $commandsToFake = [])
    {
        $this->kernel = $kernel;

        $this->commandsToFake = Arr::wrap($commandsToFake);
    }

    /**
     * Assert if a command was called based on a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|int|null  $callback
     * @return void
     */
    public function assertCalled($command, $callback = null)
    {
        if (is_numeric($callback)) {
            return $this->assertCalledTimes($command, $callback);
        }

        PHPUnit::assertTrue(
            $this->called($command, $callback)->count() > 0,
            "The expected [{$command}] command was not called."
        );
    }

    /**
     * Assert if a command was called a number of times.
     *
     * @param  string  $command
     * @param  int  $times
     * @return void
     */
    public function assertCalledTimes($command, $times = 1)
    {
        $count = $this->called($command)->count();

        PHPUnit::assertSame(
            $times,
            $count,
            "The expected [{$command}] command was called {$count} times instead of {$times} times."
        );
    }

    /**
     * Get all of the jobs matching a truth-test callback.
     *
     * @param  string  $command
     * @param  callable|null  $callback
     * @return \Illuminate\Support\Collection
     */
    public function called($command, $callback = null)
    {
        if (! $this->hasCalled($command)) {
            return collect();
        }

        $callback = $callback ?: function () {
            return true;
        };

        return collect($this->commands[$command])->filter(function ($command) use ($callback) {
            return $callback($command);
        });
    }

    /**
     * Determine if there are any stored commands for a given class.
     *
     * @param  string  $command
     * @return bool
     */
    public function hasCalled($command)
    {
        return isset($this->commands[$command]) && ! empty($this->commands[$command]);
    }

    /**
     * Call a command to its appropriate handler.
     *
     * @param  mixed  $command
     * @return mixed
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        if ($this->shouldFakeCommand($command)) {
            $this->commands[$command][] = $command;
        } else {
            return $this->kernel->call($command);
        }
    }

    /**
     * Determine if an command should be faked or actually called.
     *
     * @param  mixed  $command
     * @return bool
     */
    protected function shouldFakeCommand($command)
    {
        if (empty($this->commandsToFake)) {
            return true;
        }

        return collect($this->commandsToFake)
            ->filter(function ($item) use ($command) {
                return $item === $command;
            })->isNotEmpty();
    }

    public function handle($input, $output = null)
    {
        // TODO: Implement handle() method.
    }

    public function queue($command, array $parameters = [])
    {
        // TODO: Implement queue() method.
    }

    public function all()
    {
        // TODO: Implement all() method.
    }

    public function output()
    {
        // TODO: Implement output() method.
    }

    public function terminate($input, $status)
    {
        // TODO: Implement terminate() method.
    }
}
