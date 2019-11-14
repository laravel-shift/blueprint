<?php

namespace Tests\Feature\Generator\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\ViewGenerator;
use Blueprint\Lexers\StatementLexer;
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
        $this->files->expects('get')
            ->with('stubs/view.stub')
            ->andReturn(file_get_contents('stubs/view.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['controllers' => []]));
    }

    /**
     * @test
     */
    public function output_writes_nothing_without_render_statements()
    {
        $this->files->expects('get')
            ->with('stubs/view.stub')
            ->andReturn(file_get_contents('stubs/view.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('definitions/controllers-only.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_views_for_render_statements()
    {
        $template = file_get_contents('stubs/view.stub');
        $this->files->expects('get')
            ->with('stubs/view.stub')
            ->andReturn($template);

        $this->files->expects('exists')
            ->with('resources/user/index.blade.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('resources/user/index.blade.php', str_replace('DummyView', 'user.index', $template));

        $this->files->expects('exists')
            ->with('resources/user/create.blade.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('resources/user/create.blade.php', str_replace('DummyView', 'user.create', $template));

        $this->files->expects('exists')
            ->with('resources/post/show.blade.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('resources/post/show.blade.php', str_replace('DummyView', 'post.show', $template));

        $tokens = $this->blueprint->parse($this->fixture('definitions/render-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['resources/user/index.blade.php', 'resources/user/create.blade.php', 'resources/post/show.blade.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_views()
    {
        $this->files->expects('get')
            ->with('stubs/view.stub')
            ->andReturn(file_get_contents('stubs/view.stub'));

        $this->files->expects('exists')
            ->with('resources/user/index.blade.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('resources/user/create.blade.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('resources/post/show.blade.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('definitions/render-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }
}