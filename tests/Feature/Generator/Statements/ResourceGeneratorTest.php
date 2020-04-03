<?php

namespace Tests\Feature\Generator\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\ResourceGenerator;
use Blueprint\Lexers\StatementLexer;
use Tests\TestCase;

/**
 * @see ResourceGenerator
 */
class ResourceGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var ResourceGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new ResourceGenerator($this->files);

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
            ->with('resource.stub')
            ->andReturn(file_get_contents('stubs/resource.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['controllers' => []]));
    }

    /**
     * @test
     */
    public function output_writes_nothing_without_resource_statements()
    {
        $this->files->expects('stub')
            ->with('resource.stub')
            ->andReturn(file_get_contents('stubs/resource.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('definitions/controllers-only.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_resources_for_render_statements()
    {
        $template = file_get_contents('stubs/resource.stub');
        $this->files->expects('stub')
            ->with('resource.stub')
            ->andReturn($template);

        $this->files->shouldReceive('exists')
            ->twice()
            ->with('app/Http/Resources')
            ->andReturns(false, true);
        $this->files->expects('makeDirectory')
            ->with('app/Http/Resources', 0755, true);

        $this->files->expects('exists')
            ->twice()
            ->with('app/Http/Resources/User.php')
            ->andReturns(false, true);
        $this->files->expects('put')
            ->with('app/Http/Resources/User.php', $this->fixture('resources/user.php'));

        $this->files->expects('exists')
            ->twice()
            ->with('app/Http/Resources/UserCollection.php')
            ->andReturns(false, true);
        $this->files->expects('put')
            ->with('app/Http/Resources/UserCollection.php', $this->fixture('resources/user-collection.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/resource-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Http/Resources/UserCollection.php', 'app/Http/Resources/User.php']], $this->subject->output($tree));
    }
}
