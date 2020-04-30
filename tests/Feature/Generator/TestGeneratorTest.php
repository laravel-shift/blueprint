<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\TestGenerator;
use Blueprint\Lexers\StatementLexer;
use Tests\TestCase;

/**
 * @see TestGenerator
 */
class TestGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var TestGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new TestGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->files->expects('get')
            ->with('stubs/test/class.stub')
            ->andReturn(file_get_contents('stubs/test/class.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['controllers' => []]));
    }

    /**
     * @test
     * @dataProvider controllerTreeDataProvider
     */
    public function output_generates_test_for_controller_tree($definition, $path, $test)
    {
        $this->files->expects('get')
            ->with('stubs/test/class.stub')
            ->andReturn(file_get_contents('stubs/test/class.stub'));

        $this->files->expects('get')
            ->with('stubs/test/case.stub')
            ->andReturn(file_get_contents('stubs/test/case.stub'));
        $dirname = dirname($path);
        $this->files->expects('exists')
            ->with($dirname)
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with($dirname, 0755, true);
        $this->files->expects('put')
            ->with($path, $this->fixture($test));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_test_for_controller_tree_using_cached_model()
    {
        $this->files->expects('get')
            ->with('stubs/test/class.stub')
            ->andReturn(file_get_contents('stubs/test/class.stub'));

        $this->files->expects('get')
            ->with('stubs/test/case.stub')
            ->andReturn(file_get_contents('stubs/test/case.stub'));
        $this->files->expects('exists')
            ->with('tests/Feature/Http/Controllers')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('tests/Feature/Http/Controllers', 0755, true);
        $this->files->expects('put')
            ->with('tests/Feature/Http/Controllers/UserControllerTest.php', $this->fixture('tests/reference-cache.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/reference-cache.bp'));
        $tokens['cache'] = [
            'User' => [
                'email' => 'string',
                'password' => 'string',
            ]
        ];
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['tests/Feature/Http/Controllers/UserControllerTest.php']], $this->subject->output($tree));
    }

    public function controllerTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/readme-example.php'],
            ['definitions/respond-statements.bp', 'tests/Feature/Http/Controllers/Api/PostControllerTest.php', 'tests/respond-statements.php'],
            ['definitions/full-crud-example.bp', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/full-crud-example.php'],
        ];
    }
}
