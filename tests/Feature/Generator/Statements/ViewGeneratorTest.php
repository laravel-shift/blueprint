<?php

namespace Tests\Feature\Generator\Statements;

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

    private $files;

    /** @var ViewGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
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
        $this->files->expects('stub')
            ->with('view.stub')
            ->andReturn($this->stub('view.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     */
    public function output_writes_nothing_without_render_statements()
    {
        $this->files->expects('stub')
            ->with('view.stub')
            ->andReturn($this->stub('view.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/controllers-only.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_views_for_render_statements()
    {
        $template = $this->stub('view.stub');
        $this->files->expects('stub')
            ->with('view.stub')
            ->andReturn($template);

        $this->files->shouldReceive('exists')
            ->times(2)
            ->with('resources/views/user')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('resources/views/user/index.blade.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('resources/views/user/index.blade.php', str_replace('{{ view }}', 'user.index', $template));

        $this->files->expects('exists')
            ->with('resources/views/user/create.blade.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('resources/views/user/create.blade.php', str_replace('{{ view }}', 'user.create', $template));

        $this->files->expects('exists')
            ->with('resources/views/post')
            ->andReturns(false, true);
        $this->files->expects('exists')
            ->with('resources/views/post/show.blade.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('resources/views/post', 0755, true);
        $this->files->expects('put')
            ->with('resources/views/post/show.blade.php', str_replace('{{ view }}', 'post.show', $template));

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['resources/views/user/index.blade.php', 'resources/views/user/create.blade.php', 'resources/views/post/show.blade.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_views()
    {
        $this->files->expects('stub')
            ->with('view.stub')
            ->andReturn($this->stub('view.stub'));

        $this->files->expects('exists')
            ->with('resources/views/user/index.blade.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('resources/views/user/create.blade.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('resources/views/post/show.blade.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }
}
