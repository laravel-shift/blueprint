<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\ControllerGenerator;
use Blueprint\Lexers\StatementLexer;
use Tests\TestCase;

/**
 * @see ControllerGenerator
 */
class ControllerGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var ControllerGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new ControllerGenerator($this->files);

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
            ->with('stubs/controller/class.stub')
            ->andReturn(file_get_contents('stubs/controller/class.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['controllers' => []]));
    }

    /**
     * @test
     * @dataProvider controllerTreeDataProvider
     */
    public function output_writes_migration_for_controller_tree($definition, $path, $controller)
    {
        static $iteration = 0;

        $this->files->expects('get')
            ->with('stubs/controller/class.stub')
            ->andReturn(file_get_contents('stubs/controller/class.stub'));

        if ($iteration === 0) {
            $this->files->expects('get')
                ->with('stubs/controller/method.stub')
                ->andReturn(file_get_contents('stubs/controller/method.stub'));
        }

        $this->files->expects('put')
            ->with($path, $this->fixture($controller));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
        ++$iteration;
    }


    public function controllerTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'app/Http/Controllers/PostController.php', 'controllers/readme-example.php'],
            ['definitions/crazy-eloquent.bp', 'app/Http/Controllers/PostController.php', 'controllers/crazy-eloquent.php'],
        ];
    }
}
