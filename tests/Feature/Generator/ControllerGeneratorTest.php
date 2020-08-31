<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\ControllerGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
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
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     * @dataProvider controllerTreeDataProvider
     */
    public function output_writes_migration_for_controller_tree($definition, $path, $controller)
    {
        $this->files->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->files->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));

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
        $definition = 'drafts/custom-models-namespace.yaml';
        $path = 'app/Http/Controllers/TagController.php';
        $controller = 'controllers/custom-models-namespace.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->files->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->files->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));

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
    public function output_works_for_pascal_case_definition()
    {
        $this->files->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->files->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'))
            ->twice();

        $certificateController = 'app/Http/Controllers/CertificateController.php';
        $certificateTypeController = 'app/Http/Controllers/CertificateTypeController.php';

        $this->files->expects('exists')
            ->with(dirname($certificateController))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($certificateController, $this->fixture('controllers/certificate-controller.php'));

        $this->files->expects('exists')
            ->with(dirname($certificateTypeController))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($certificateTypeController, $this->fixture('controllers/certificate-type-controller.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/pascal-case.yaml'));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$certificateController, $certificateTypeController]], $this->subject->output($tree));
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
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->files->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));

        $this->files->expects('exists')
            ->with('src/path/Other/Http')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('src/path/Other/Http', 0755, true);
        $this->files->expects('put')
            ->with('src/path/Other/Http/UserController.php', $this->fixture('controllers/controller-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/simple-controller.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Other/Http/UserController.php']], $this->subject->output($tree));
    }

    public function controllerTreeDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'app/Http/Controllers/PostController.php', 'controllers/readme-example.php'],
            ['drafts/readme-example-notification-facade.yaml', 'app/Http/Controllers/PostController.php', 'controllers/readme-example-notification-facade.php'],
            ['drafts/readme-example-notification-model.yaml', 'app/Http/Controllers/PostController.php', 'controllers/readme-example-notification-model.php'],
            ['drafts/crazy-eloquent.yaml', 'app/Http/Controllers/PostController.php', 'controllers/crazy-eloquent.php'],
            ['drafts/nested-components.yaml', 'app/Http/Controllers/Admin/UserController.php', 'controllers/nested-components.php'],
            ['drafts/respond-statements.yaml', 'app/Http/Controllers/Api/PostController.php', 'controllers/respond-statements.php'],
            ['drafts/resource-statements.yaml', 'app/Http/Controllers/UserController.php', 'controllers/resource-statements.php'],
            ['drafts/save-without-validation.yaml', 'app/Http/Controllers/PostController.php', 'controllers/save-without-validation.php'],
            ['drafts/api-routes-example.yaml', 'app/Http/Controllers/Api/CertificateController.php', 'controllers/api-routes-example.php'],
        ];
    }
}
