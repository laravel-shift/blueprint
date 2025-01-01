<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\ControllerGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see ControllerGenerator
 */
final class ControllerGeneratorTest extends TestCase
{
    private $blueprint;

    /** @var ControllerGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ControllerGenerator($this->filesystem);

        $this->blueprint = new Blueprint;
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer);
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer));
        $this->blueprint->registerGenerator($this->subject);
    }

    #[Test]
    public function output_writes_nothing_for_empty_tree(): void
    {
        $this->filesystem->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    #[Test]
    #[DataProvider('controllerTreeDataProvider')]
    public function output_generates_controllers_for_tree($definition, $path, $controller): void
    {
        $this->filesystem->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->filesystem->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($controller));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_controllers_with_models_using_custom_namespace(): void
    {
        $definition = 'drafts/custom-models-namespace.yaml';
        $path = 'app/Http/Controllers/TagController.php';
        $controller = 'controllers/custom-models-namespace.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->filesystem->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->filesystem->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($controller));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    #[Test]
    public function output_works_for_pascal_case_definition(): void
    {
        $this->filesystem->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->filesystem->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'))
            ->twice();

        $certificateController = 'app/Http/Controllers/CertificateController.php';
        $certificateTypeController = 'app/Http/Controllers/CertificateTypeController.php';

        $this->filesystem->expects('exists')
            ->with(dirname($certificateController))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($certificateController, $this->fixture('controllers/certificate-controller.php'));

        $this->filesystem->expects('exists')
            ->with(dirname($certificateTypeController))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($certificateTypeController, $this->fixture('controllers/certificate-type-controller.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/pascal-case.yaml'));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$certificateController, $certificateTypeController]], $this->subject->output($tree));
    }

    #[Test]
    public function output_respects_configuration(): void
    {
        $this->app['config']->set('blueprint.app_path', 'src/path');
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.models_namespace', '');
        $this->app['config']->set('blueprint.controllers_namespace', 'Other\\Http');

        $this->filesystem->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->filesystem->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));

        $this->filesystem->expects('exists')
            ->with('src/path/Other/Http')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('src/path/Other/Http', 0755, true);
        $this->filesystem->expects('put')
            ->with('src/path/Other/Http/UserController.php', $this->fixture('controllers/controller-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/simple-controller.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Other/Http/UserController.php']], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_controller_with_all_policies(): void
    {
        $definition = 'drafts/controller-with-all-policies.yaml';
        $path = 'app/Http/Controllers/PostController.php';
        $controller = 'controllers/with-all-policies.php';

        $this->filesystem->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->filesystem->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($controller));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_controller_with_some_policies(): void
    {
        $definition = 'drafts/controller-with-some-policies.yaml';
        $path = 'app/Http/Controllers/PostController.php';
        $controller = 'controllers/with-some-policies.php';

        $this->filesystem->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->filesystem->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($controller));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_controller_with_authorize_resource(): void
    {
        $definition = 'drafts/controller-with-authorize-resource.yaml';
        $path = 'app/Http/Controllers/PostController.php';
        $controller = 'controllers/with-authorize-resource.php';

        $this->filesystem->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->filesystem->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));
        $this->filesystem->expects('stub')
            ->with('controller.authorize-resource.stub')
            ->andReturn($this->stub('controller.authorize-resource.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($controller));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_controller_without_generating_resource_collection_classes(): void
    {
        config(['blueprint.generate_resource_collection_classes' => false]);

        $definition = 'drafts/api-resource-pagination.yaml';
        $path = 'app/Http/Controllers/PostController.php';
        $controller = 'controllers/without-generating-resource-collection-classes.php';

        $this->filesystem->expects('stub')
            ->with('controller.class.stub')
            ->andReturn($this->stub('controller.class.stub'));
        $this->filesystem->expects('stub')
            ->with('controller.method.stub')
            ->andReturn($this->stub('controller.method.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($controller));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    public static function controllerTreeDataProvider(): array
    {
        return [
            ['drafts/readme-example.yaml', 'app/Http/Controllers/PostController.php', 'controllers/readme-example.php'],
            ['drafts/readme-example-notification-facade.yaml', 'app/Http/Controllers/PostController.php', 'controllers/readme-example-notification-facade.php'],
            ['drafts/readme-example-notification-model.yaml', 'app/Http/Controllers/PostController.php', 'controllers/readme-example-notification-model.php'],
            ['drafts/crazy-eloquent.yaml', 'app/Http/Controllers/PostController.php', 'controllers/crazy-eloquent.php'],
            ['drafts/longhand-controller-name.yaml', 'app/Http/Controllers/UserController.php', 'controllers/longhand-controller-name.php'],
            ['drafts/nested-components.yaml', 'app/Http/Controllers/Admin/UserController.php', 'controllers/nested-components.php'],
            ['drafts/respond-statements.yaml', 'app/Http/Controllers/Api/PostController.php', 'controllers/respond-statements.php'],
            ['drafts/resource-statements.yaml', 'app/Http/Controllers/UserController.php', 'controllers/resource-statements.php'],
            ['drafts/inertia-render.yaml', 'app/Http/Controllers/CustomerController.php', 'controllers/inertia-render.php'],
            ['drafts/save-without-validation.yaml', 'app/Http/Controllers/PostController.php', 'controllers/save-without-validation.php'],
            ['drafts/api-resource-pagination.yaml', 'app/Http/Controllers/PostController.php', 'controllers/api-resource-pagination.php'],
            ['drafts/api-resource-nested.yaml', 'app/Http/Controllers/CommentController.php', 'controllers/api-resource-nested.php'],
            ['drafts/api-routes-example.yaml', 'app/Http/Controllers/Api/CertificateController.php', 'controllers/api-routes-example.php'],
            ['drafts/invokable-controller.yaml', 'app/Http/Controllers/ReportController.php', 'controllers/invokable-controller.php'],
            ['drafts/invokable-controller-shorthand.yaml', 'app/Http/Controllers/ReportController.php', 'controllers/invokable-controller-shorthand.php'],
        ];
    }
}
