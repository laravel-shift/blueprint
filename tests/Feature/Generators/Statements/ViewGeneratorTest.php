<?php

namespace Tests\Feature\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\ViewGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use Tests\TestCase;

/**
 * @see ViewGenerator
 */
class ViewGeneratorTest extends TestCase
{
    private $blueprint;

    protected $files;

    /** @var ViewGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ViewGenerator($this->files);

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
            ->with('view.stub')
            ->andReturn($this->stub('view.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     */
    public function output_writes_nothing_without_render_statements()
    {
        $this->filesystem->expects('stub')
            ->with('view.stub')
            ->andReturn($this->stub('view.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/controllers-only.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_views_for_render_statements()
    {
        $this->filesystem->expects('stub')
            ->with('view.stub')
            ->andReturn($this->stub('view.stub'));

        $this->filesystem->shouldReceive('exists')
            ->times(2)
            ->with('resources/views/user')
            ->andReturnTrue();
        $this->filesystem->expects('exists')
            ->with('resources/views/user/index.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('resources/views/user/index.blade.php', $this->fixture('views/user.index.blade.php'));

        $this->filesystem->expects('exists')
            ->with('resources/views/user/create.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with('resources/views/user/create.blade.php', $this->fixture('views/user.create.blade.php'));

        $this->filesystem->expects('exists')
            ->with('resources/views/post')
            ->andReturns(false, true);
        $this->filesystem->expects('exists')
            ->with('resources/views/post/show.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('resources/views/post', 0755, true);
        $this->filesystem->expects('put')
            ->with('resources/views/post/show.blade.php', $this->fixture('views/post.show.blade.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['resources/views/user/index.blade.php', 'resources/views/user/create.blade.php', 'resources/views/post/show.blade.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_views()
    {
        $this->filesystem->expects('stub')
            ->with('view.stub')
            ->andReturn($this->stub('view.stub'));

        $this->filesystem->expects('exists')
            ->with('resources/views/user/index.blade.php')
            ->andReturnTrue();
        $this->filesystem->expects('exists')
            ->with('resources/views/user/create.blade.php')
            ->andReturnTrue();
        $this->filesystem->expects('exists')
            ->with('resources/views/post/show.blade.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }
}
