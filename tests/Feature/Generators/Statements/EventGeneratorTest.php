<?php

namespace Tests\Feature\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\EventGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use Tests\TestCase;

/**
 * @see EventGenerator
 */
class EventGeneratorTest extends TestCase
{
    private $blueprint;

    protected $files;

    /** @var EventGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new EventGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->filesystem->expects('stub')
            ->with('event.stub')
            ->andReturn($this->stub('event.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     */
    public function output_writes_nothing_tree_without_validate_statements()
    {
        $this->filesystem->expects('stub')
            ->with('event.stub')
            ->andReturn($this->stub('event.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_events()
    {
        $this->filesystem->expects('stub')
            ->with('event.stub')
            ->andReturn($this->stub('event.stub'));

        $this->filesystem->expects('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));

        $this->filesystem->shouldReceive('exists')
            ->twice()
            ->with('app/Events')
            ->andReturns(false, true);
        $this->filesystem->expects('exists')
            ->with('app/Events/UserCreated.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('app/Events', 0755, true);
        $this->filesystem->expects('put')
            ->with('app/Events/UserCreated.php', $this->fixture('events/user-created.php'));

        $this->filesystem->expects('exists')
            ->with('app/Events/UserDeleted.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('app/Events/UserDeleted.php', $this->fixture('events/user-deleted.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/fire-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Events/UserCreated.php', 'app/Events/UserDeleted.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_events()
    {
        $this->filesystem->expects('stub')
            ->with('event.stub')
            ->andReturn($this->stub('event.stub'));

        $this->filesystem->expects('exists')
            ->with('app/Events/UserCreated.php')
            ->andReturnTrue();
        $this->filesystem->expects('exists')
            ->with('app/Events/UserDeleted.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/fire-statements.yaml'));
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

        $this->filesystem->expects('stub')
            ->with('event.stub')
            ->andReturn($this->stub('event.stub'));

        $this->filesystem->expects('exists')
            ->with('src/path/Events')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('src/path/Events', 0755, true);
        $this->filesystem->expects('exists')
            ->with('src/path/Events/NewPost.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('src/path/Events/NewPost.php', $this->fixture('events/event-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Events/NewPost.php']], $this->subject->output($tree));
    }
}
