<?php

namespace Tests\Feature\Generator\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\JobGenerator;
use Blueprint\Lexers\StatementLexer;
use Tests\TestCase;

/**
 * @see JobGenerator
 */
class JobGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var JobGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new JobGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->files->expects('get')
            ->with('stubs/job.stub')
            ->andReturn(file_get_contents('stubs/job.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['controllers' => []]));
    }

    /**
     * @test
     */
    public function output_writes_nothing_tree_without_validate_statements()
    {
        $this->files->expects('get')
            ->with('stubs/job.stub')
            ->andReturn(file_get_contents('stubs/job.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('definitions/render-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_jobs()
    {
        $this->files->expects('get')
            ->with('stubs/job.stub')
            ->andReturn(file_get_contents('stubs/job.stub'));

        $this->files->expects('get')
            ->with('stubs/partials/constructor.stub')
            ->andReturn(file_get_contents('stubs/partials/constructor.stub'));

        $this->files->expects('exists')
            ->with('app/Jobs/CreateUser.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Jobs/CreateUser.php', $this->fixture('jobs/create-user.php'));

        $this->files->expects('exists')
            ->with('app/Jobs/DeleteRole.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Jobs/DeleteRole.php', $this->fixture('jobs/delete-user.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/dispatch-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Jobs/CreateUser.php', 'app/Jobs/DeleteRole.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_jobs()
    {
        $this->files->expects('get')
            ->with('stubs/job.stub')
            ->andReturn(file_get_contents('stubs/job.stub'));

        $this->files->expects('exists')
            ->with('app/Jobs/CreateUser.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('app/Jobs/DeleteRole.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('definitions/dispatch-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }
}