<?php

namespace Tests\Feature\Generator\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\JobGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
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
        $this->files->expects('stub')
            ->with('job.stub')
            ->andReturn($this->stub('job.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     */
    public function output_writes_nothing_tree_without_validate_statements()
    {
        $this->files->expects('stub')
            ->with('job.stub')
            ->andReturn($this->stub('job.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_jobs()
    {
        $this->files->expects('stub')
            ->with('job.stub')
            ->andReturn($this->stub('job.stub'));

        $this->files->expects('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));

        $this->files->shouldReceive('exists')
            ->twice()
            ->with('app/Jobs')
            ->andReturns(false, true);
        $this->files->expects('exists')
            ->with('app/Jobs/CreateUser.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('app/Jobs', 0755, true);
        $this->files->expects('put')
            ->with('app/Jobs/CreateUser.php', $this->fixture('jobs/create-user.php'));

        $this->files->expects('exists')
            ->with('app/Jobs/DeleteRole.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Jobs/DeleteRole.php', $this->fixture('jobs/delete-user.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/dispatch-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Jobs/CreateUser.php', 'app/Jobs/DeleteRole.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_jobs()
    {
        $this->files->expects('stub')
            ->with('job.stub')
            ->andReturn($this->stub('job.stub'));

        $this->files->expects('exists')
            ->with('app/Jobs/CreateUser.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('app/Jobs/DeleteRole.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/dispatch-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_respects_configuration()
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.app_path', 'src/path');

        $this->files->expects('stub')
            ->with('job.stub')
            ->andReturn($this->stub('job.stub'));

        $this->files->expects('exists')
            ->with('src/path/Jobs')
            ->andReturnFalse();
        $this->files->expects('exists')
            ->with('src/path/Jobs/SyncMedia.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('src/path/Jobs', 0755, true);
        $this->files->expects('put')
            ->with('src/path/Jobs/SyncMedia.php', $this->fixture('jobs/job-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Jobs/SyncMedia.php']], $this->subject->output($tree));
    }
}
