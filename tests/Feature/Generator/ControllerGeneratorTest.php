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
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->files->expects('stub')
            ->with('controller/class.stub')
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
        $this->files->expects('stub')
            ->with('controller/class.stub')
            ->andReturn(file_get_contents('stubs/controller/class.stub'));
        $this->files->expects('stub')
            ->with('controller/method.stub')
            ->andReturn(file_get_contents('stubs/controller/method.stub'));

        $this->files->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($path, $this->fixture($controller));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
    * @test
    */
    public function output_generates_controllers_with_models_with_custom_namespace_correctly()
    {
        $definition = 'definitions/custom-models-namespace.bp';
        $path = 'app/Http/Controllers/TagController.php';
        $controller = 'controllers/custom-models-namespace.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->files->expects('stub')
            ->with('controller/class.stub')
            ->andReturn(file_get_contents('stubs/controller/class.stub'));
        $this->files->expects('stub')
            ->with('controller/method.stub')
            ->andReturn(file_get_contents('stubs/controller/method.stub'));

        $this->files->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($path, $this->fixture($controller));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_respects_configuration()
    {
        $this->app['config']->set('blueprint.app_path', 'src/path');
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.controllers_namespace', 'Other\\Http');

        $this->files->expects('stub')
            ->with('controller/class.stub')
            ->andReturn(file_get_contents('stubs/controller/class.stub'));
        $this->files->expects('stub')
            ->with('controller/method.stub')
            ->andReturn(file_get_contents('stubs/controller/method.stub'));

        $this->files->expects('exists')
            ->with('src/path/Other/Http')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('src/path/Other/Http', 0755, true);
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
            ['definitions/respond-statements.bp', 'app/Http/Controllers/Api/PostController.php', 'controllers/respond-statements.php'],
            ['definitions/resource-statements.bp', 'app/Http/Controllers/UserController.php', 'controllers/resource-statements.php'],
        ];
    }
}
