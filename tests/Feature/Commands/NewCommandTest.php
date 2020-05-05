<?php

namespace Tests\Feature\Commands;

use Blueprint\BlueprintServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;

class NewCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            BlueprintServiceProvider::class
        ];
    }

    /**
     * @test
     */
    public function it_creates_an_empty_draft_file()
    {
        $this->withoutMockingConsoleOutput();

        $this->artisan('blueprint:new');

        $output = Artisan::output();

        $this->assertFileExists('draft.yaml');

        $this->assertEquals('draft.yaml file created succesfully!' . PHP_EOL, $output);
    }

    /**
     * @test
     */
    public function it_returns_error_if_draft_file_exists()
    {
        $this->withoutMockingConsoleOutput();

        $this->artisan('blueprint:new');

        $output = Artisan::output();

        $this->assertFileExists('draft.yaml');

        $this->assertEquals('The file already exists!' . PHP_EOL, $output);
    }
}
