<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\ModelGenerator;
use Blueprint\Tree;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ModelGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var ModelGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->modelStub = version_compare(App::version(), '8.0.0', '>=') ? 'model.class.stub' : 'model.class.no-factory.stub';
        $this->subject = new ModelGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_generates_nothing_for_empty_tree()
    {
        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['models' => []])));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     * @dataProvider laravel8ModelTreeDataProvider
     */
    public function output_generates_models($definition, $path, $model)
    {
        if ($model === 'models/return-type-declarations.php') {
            $this->app['config']->set('blueprint.use_return_types', true);
        }
        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));

        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        if (in_array($definition, ['drafts/nested-components.yaml', 'drafts/resource-statements.yaml'])) {
            $this->files->expects('stub')
                ->with('model.hidden.stub')
                ->andReturn($this->stub('model.hidden.stub'));
        }

        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        $this->files->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->shouldReceive('stub')
            ->with('model.method.comment.stub')
            ->andReturn($this->stub('model.method.comment.stub'));

        $this->files->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($path, $this->fixture($model));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel7
     * @dataProvider modelTreeDataProvider
     */
    public function output_generates_models_l7($definition, $path, $model)
    {
        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));

        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        if (in_array($definition, ['drafts/nested-components.yaml', 'drafts/resource-statements.yaml'])) {
            $this->files->expects('stub')
                ->with('model.hidden.stub')
                ->andReturn($this->stub('model.hidden.stub'));
        }

        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        if (in_array($definition, ['drafts/readme-example.yaml', 'drafts/all-column-types.yaml'])) {
            $this->files->expects('stub')
                ->with('model.dates.stub')
                ->andReturn($this->stub('model.dates.stub'));
        }

        $this->files->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->shouldReceive('stub')
            ->with('model.method.comment.stub')
            ->andReturn($this->stub('model.method.comment.stub'));

        $this->files->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($path, $this->fixture(str_replace('models', 'models', $model)));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     * @dataProvider modelTreeDataProvider
     */
    public function output_generates_models_l6($definition, $path, $model)
    {
        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));

        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        if (in_array($definition, ['drafts/nested-components.yaml', 'drafts/resource-statements.yaml'])) {
            $this->files->expects('stub')
                ->with('model.hidden.stub')
                ->andReturn($this->stub('model.hidden.stub'));
        }

        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        if (in_array($definition, ['drafts/readme-example.yaml', 'drafts/all-column-types.yaml'])) {
            $this->files->expects('stub')
                ->with('model.dates.stub')
                ->andReturn($this->stub('model.dates.stub'));
        }

        $this->files->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->shouldReceive('stub')
            ->with('model.method.comment.stub')
            ->andReturn($this->stub('model.method.comment.stub'));

        $this->files->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($path, $this->fixture(str_replace('models', 'models', $model)));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_works_for_pascal_case_definition()
    {
        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));
        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'))
            ->twice();
        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'))
            ->twice();
        $this->files->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'))
            ->twice();

        $certificateModel = 'app/Certificate.php';
        $certificateTypeModel = 'app/CertificateType.php';

        $this->files->expects('exists')
            ->with(dirname($certificateModel))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($certificateModel, $this->fixture('models/certificate-pascal-case-example-laravel8.php'));

        $this->files->expects('exists')
            ->with(dirname($certificateTypeModel))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($certificateTypeModel, $this->fixture('models/certificate-type-pascal-case-example-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/pascal-case.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$certificateModel, $certificateTypeModel]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_generates_relationships()
    {
        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));
        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->files->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/Subscription.php', $this->fixture('models/model-relationships-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/model-relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Subscription.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_generates_polymorphic_relationships()
    {
        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));
        $this->files->expects('stub')
            ->times(3)
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->files->expects('stub')
            ->times(3)
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->files->expects('stub')
            ->times(3)
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/Post.php', $this->fixture('models/post-polymorphic-relationship-laravel8.php'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/User.php', $this->fixture('models/user-polymorphic-relationship-laravel8.php'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/Image.php', $this->fixture('models/image-polymorphic-relationship-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/polymorphic-relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Post.php', 'app/User.php', 'app/Image.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_generates_disabled_auto_columns()
    {
        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));
        $this->files->expects('stub')
            ->with('model.timestamps.stub')
            ->andReturn($this->stub('model.timestamps.stub'));
        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->files->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/State.php', $this->fixture('models/disable-auto-columns-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/disable-auto-columns.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/State.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_respects_configuration()
    {
        $this->app['config']->set('blueprint.app_path', 'src/path');
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));

        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        $this->files->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->expects('exists')
            ->with('src/path/Models')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('src/path/Models', 0755, true);
        $this->files->expects('put')
            ->with('src/path/Models/Comment.php', $this->fixture('models/model-configured-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Models/Comment.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     * @dataProvider laravel8DocBlockModelsDataProvider
     */
    public function output_generates_phpdoc_for_model($definition, $path, $model)
    {
        $this->app['config']->set('blueprint.generate_phpdocs', true);

        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));

        if ($definition === 'drafts/disable-auto-columns.yaml') {
            $this->files->expects('stub')
                ->with('model.timestamps.stub')
                ->andReturn($this->stub('model.timestamps.stub'));
        }

        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        $this->files->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->shouldReceive('stub')
            ->with('model.method.comment.stub')
            ->andReturn($this->stub('model.method.comment.stub'));

        $this->files->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();

        $this->files->expects('put')
            ->with($path, $this->fixture($model));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel7
     * @dataProvider docBlockModelsDataProvider
     */
    public function output_generates_phpdoc_for_model_l7($definition, $path, $model)
    {
        $this->app['config']->set('blueprint.generate_phpdocs', true);

        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));

        if ($definition === 'drafts/disable-auto-columns.yaml') {
            $this->files->expects('stub')
                ->with('model.timestamps.stub')
                ->andReturn($this->stub('model.timestamps.stub'));
        }

        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        if ($definition === 'drafts/readme-example.yaml') {
            $this->files->expects('stub')
                ->with('model.dates.stub')
                ->andReturn($this->stub('model.dates.stub'));
        }

        $this->files->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->shouldReceive('stub')
            ->with('model.method.comment.stub')
            ->andReturn($this->stub('model.method.comment.stub'));

        $this->files->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();

        $this->files->expects('put')
            ->with($path, $this->fixture(str_replace('models', 'models', $model)));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     * @dataProvider docBlockModelsDataProvider
     */
    public function output_generates_phpdoc_for_model_l6($definition, $path, $model)
    {
        $this->app['config']->set('blueprint.generate_phpdocs', true);

        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));

        if ($definition === 'drafts/disable-auto-columns.yaml') {
            $this->files->expects('stub')
                ->with('model.timestamps.stub')
                ->andReturn($this->stub('model.timestamps.stub'));
        }

        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        if ($definition === 'drafts/readme-example.yaml') {
            $this->files->expects('stub')
                ->with('model.dates.stub')
                ->andReturn($this->stub('model.dates.stub'));
        }

        $this->files->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->shouldReceive('stub')
            ->with('model.method.comment.stub')
            ->andReturn($this->stub('model.method.comment.stub'));

        $this->files->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();

        $this->files->expects('put')
            ->with($path, $this->fixture(str_replace('models', 'models', $model)));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_generates_models_with_guarded_property_when_config_option_is_set()
    {
        $this->app['config']->set('blueprint.use_guarded', true);

        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));

        $this->files->expects('stub')
            ->with('model.guarded.stub')
            ->andReturn($this->stub('model.guarded.stub'));

        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        $this->files->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->expects('exists')
            ->with(dirname('app/Comment.php'))
            ->andReturnTrue();

        $this->files->expects('put')
            ->with('app/Comment.php', $this->fixture('models/model-guarded-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/model-guarded.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Comment.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_generates_models_with_custom_namespace_correctly()
    {
        $definition = 'drafts/custom-models-namespace.yaml';
        $path = 'app/Models/Tag.php';
        $model = 'models/custom-models-namespace-laravel8.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));
        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->files->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->files->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($path, $this->fixture($model));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_generates_models_with_custom_pivot_columns()
    {
        $this->files->expects('stub')
            ->with($this->modelStub)
            ->andReturn($this->stub($this->modelStub));
        $this->files->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->files->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->files->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));
        $this->files->expects('stub')
            ->with('model.hidden.stub')
            ->andReturn($this->stub('model.hidden.stub'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/User.php', $this->fixture('models/custom-pivot-table-name-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/custom-pivot-table-name.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/User.php']], $this->subject->output($tree));
    }

    public function modelTreeDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'app/Post.php', 'models/readme-example.php'],
            ['drafts/with-timezones.yaml', 'app/Comment.php', 'models/comment.php'],
            ['drafts/soft-deletes.yaml', 'app/Comment.php', 'models/soft-deletes.php'],
            ['drafts/relationships.yaml', 'app/Comment.php', 'models/relationships.php'],
            ['drafts/unconventional.yaml', 'app/Team.php', 'models/unconventional.php'],
            ['drafts/nested-components.yaml', 'app/Admin/User.php', 'models/nested-components.php'],
            ['drafts/resource-statements.yaml', 'app/User.php', 'models/resource-statements.php'],
            ['drafts/all-column-types.yaml', 'app/AllType.php', 'models/all-column-types.php'],
            ['drafts/alias-relationships.yaml', 'app/Salesman.php', 'models/alias-relationships.php'],
            ['drafts/uuid-shorthand-invalid-relationship.yaml', 'app/AgeCohort.php', 'models/uuid-shorthand-invalid-relationship.php'],
        ];
    }

    public function docBlockModelsDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'app/Post.php', 'models/readme-example-phpdoc.php'],
            ['drafts/soft-deletes.yaml', 'app/Comment.php', 'models/soft-deletes-phpdoc.php'],
            ['drafts/relationships.yaml', 'app/Comment.php', 'models/relationships-phpdoc.php'],
            ['drafts/disable-auto-columns.yaml', 'app/State.php', 'models/disable-auto-columns-phpdoc.php'],
            ['drafts/foreign-key-shorthand.yaml', 'app/Comment.php', 'models/foreign-key-shorthand-phpdoc.php'],
            ['drafts/optimize.yaml', 'app/Optimize.php', 'models/optimize.php'],
        ];
    }

    public function laravel8ModelTreeDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'app/Post.php', 'models/readme-example-laravel8.php'],
            ['drafts/with-timezones.yaml', 'app/Comment.php', 'models/comment-laravel8.php'],
            ['drafts/soft-deletes.yaml', 'app/Comment.php', 'models/soft-deletes-laravel8.php'],
            ['drafts/relationships.yaml', 'app/Comment.php', 'models/relationships-laravel8.php'],
            ['drafts/unconventional.yaml', 'app/Team.php', 'models/unconventional-laravel8.php'],
            ['drafts/nested-components.yaml', 'app/Admin/User.php', 'models/nested-components-laravel8.php'],
            ['drafts/resource-statements.yaml', 'app/User.php', 'models/resource-statements-laravel8.php'],
            ['drafts/all-column-types.yaml', 'app/AllType.php', 'models/all-column-types-laravel8.php'],
            ['drafts/alias-relationships.yaml', 'app/Salesman.php', 'models/alias-relationships-laravel8.php'],
            ['drafts/return-type-declarations.yaml', 'app/Term.php', 'models/return-type-declarations.php'],
            ['drafts/uuid-shorthand-invalid-relationship.yaml', 'app/AgeCohort.php', 'models/uuid-shorthand-invalid-relationship-laravel8.php'],
        ];
    }

    public function laravel8DocBlockModelsDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'app/Post.php', 'models/readme-example-phpdoc-laravel8.php'],
            ['drafts/soft-deletes.yaml', 'app/Comment.php', 'models/soft-deletes-phpdoc-laravel8.php'],
            ['drafts/relationships.yaml', 'app/Comment.php', 'models/relationships-phpdoc-laravel8.php'],
            ['drafts/disable-auto-columns.yaml', 'app/State.php', 'models/disable-auto-columns-phpdoc-laravel8.php'],
            ['drafts/foreign-key-shorthand.yaml', 'app/Comment.php', 'models/foreign-key-shorthand-phpdoc-laravel8.php'],
        ];
    }
}
