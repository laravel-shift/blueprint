<?php

namespace Tests\Feature\Generators;

use Tests\TestCase;
use Blueprint\Blueprint;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Generators\ControllerGenerator;

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
        $iteration++;
    }

    /**
     * @test
     */
    public function output_respects_configuration()
    {
        $this->app['config']->set('blueprint.app_path', 'src/path');
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.controllers_namespace', 'Other\\Http');

        $this->files->expects('get')
            ->with('stubs/controller/class.stub')
            ->andReturn(file_get_contents('stubs/controller/class.stub'));

        $this->files->expects('get')
            ->with('stubs/controller/method.stub')
            ->andReturn(file_get_contents('stubs/controller/method.stub'));

        $this->files->expects('put')
            ->with('src/path/Other/Http/UserController.php', $this->fixture('controllers/controller-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/simple-controller.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Other/Http/UserController.php']], $this->subject->output($tree));
    }

    public function controllerTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'app/Http/Controllers/PostController.php', 'controllers/readme-example.php'],
            ['definitions/crazy-eloquent.bp', 'app/Http/Controllers/PostController.php', 'controllers/crazy-eloquent.php'],
            ['definitions/nested-components.bp', 'app/Http/Controllers/Admin/UserController.php', 'controllers/nested-components.php'],
        ];
    }
}
