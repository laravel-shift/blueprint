<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\ModelGenerator;
use Blueprint\Tree;
use Tests\TestCase;

class ModelGeneratorTest extends TestCase
{
    private $blueprint;

    protected $files;

    /** @var ModelGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['models' => []])));
    }

    /**
     * @test
     *
     * @dataProvider modelTreeDataProvider
     */
    public function output_generates_models($definition, $path, $model)
    {
        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));

        $this->filesystem->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        if (in_array($definition, ['drafts/nested-components.yaml', 'drafts/resource-statements.yaml'])) {
            $this->filesystem->expects('stub')
                ->with('model.hidden.stub')
                ->andReturn($this->stub('model.hidden.stub'));
        }

        if ($definition === 'drafts/model-with-meta.yaml') {
            $this->filesystem->expects('stub')
                ->with('model.table.stub')
                ->andReturn($this->stub('model.table.stub'));
        }

        $this->filesystem->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        $this->filesystem->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($model));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_works_for_pascal_case_definition()
    {
        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
        $this->filesystem->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'))
            ->twice();
        $this->filesystem->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'))
            ->twice();
        $this->filesystem->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'))
            ->twice();

        $certificateModel = 'app/Models/Certificate.php';
        $certificateTypeModel = 'app/Models/CertificateType.php';

        $this->filesystem->expects('exists')
            ->with(dirname($certificateModel))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($certificateModel, $this->fixture('models/certificate-pascal-case-example.php'));

        $this->filesystem->expects('exists')
            ->with(dirname($certificateTypeModel))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($certificateTypeModel, $this->fixture('models/certificate-type-pascal-case-example.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/pascal-case.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$certificateModel, $certificateTypeModel]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_relationships()
    {
        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
        $this->filesystem->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->filesystem->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->filesystem->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/Subscription.php', $this->fixture('models/model-relationships.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/model-relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Models/Subscription.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_relationships_added_with_full_model_namespace()
    {
        $this->files->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
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
            ->with('app/Models/Recurrency.php', $this->fixture('models/model-relationships-with-full-namespace.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/model-relationships-with-full-model-namespaces.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Models/Recurrency.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_morphone_morphmany_relation_string_when_using_fqn()
    {
        $this->files->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
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
            ->with('app/Models/Flag.php', $this->fixture('models/model-relationships-morphone-morphmany-with-fqn.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/model-relationships-morphone-morphmany-with-fqn.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Models/Flag.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_polymorphic_relationships()
    {
        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
        $this->filesystem->expects('stub')
            ->times(3)
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->filesystem->expects('stub')
            ->times(3)
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->filesystem->expects('stub')
            ->times(3)
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/Post.php', $this->fixture('models/post-polymorphic-relationship.php'));

        $this->filesystem->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/User.php', $this->fixture('models/user-polymorphic-relationship.php'));

        $this->filesystem->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/Image.php', $this->fixture('models/image-polymorphic-relationship.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/polymorphic-relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Models/Post.php', 'app/Models/User.php', 'app/Models/Image.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_morphtomany_relationship_with_intermediate_models()
    {
        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
        $this->filesystem->expects('stub')
            ->times(3)
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        $this->filesystem->expects('stub')
            ->times(3)
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->filesystem->expects('stub')
            ->times(3)
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/Post.php', $this->fixture('models/post-many-to-many-polymorphic-relationship.php'));

        $this->filesystem->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/Video.php', $this->fixture('models/video-many-to-many-polymorphic-relationship.php'));

        $this->filesystem->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/Tag.php', $this->fixture('models/tag-many-to-many-polymorphic-relationship.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/many-to-many-polymorphic-relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Models/Post.php', 'app/Models/Video.php', 'app/Models/Tag.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_disabled_auto_columns()
    {
        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
        $this->filesystem->expects('stub')
            ->with('model.timestamps.stub')
            ->andReturn($this->stub('model.timestamps.stub'));
        $this->filesystem->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->filesystem->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->filesystem->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/State.php', $this->fixture('models/disable-auto-columns.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/disable-auto-columns.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Models/State.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_respects_configuration()
    {
        $this->app['config']->set('blueprint.app_path', 'src/path');
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));

        $this->filesystem->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        $this->filesystem->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        $this->filesystem->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->with('src/path/Models')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('src/path/Models', 0755, true);
        $this->filesystem->expects('put')
            ->with('src/path/Models/Comment.php', $this->fixture('models/model-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Models/Comment.php']], $this->subject->output($tree));
    }

    /**
     * @test
     *
     * @dataProvider docBlockModelsDataProvider
     */
    public function output_generates_phpdoc_for_model($definition, $path, $model)
    {
        $this->app['config']->set('blueprint.generate_phpdocs', true);

        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));

        if ($definition === 'drafts/disable-auto-columns.yaml') {
            $this->filesystem->expects('stub')
                ->with('model.timestamps.stub')
                ->andReturn($this->stub('model.timestamps.stub'));
        }

        $this->filesystem->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));

        $this->filesystem->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        $this->filesystem->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();

        $this->filesystem->expects('put')
            ->with($path, $this->fixture($model));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_models_with_guarded_property_when_config_option_is_set()
    {
        $this->app['config']->set('blueprint.use_guarded', true);

        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));

        $this->filesystem->expects('stub')
            ->with('model.guarded.stub')
            ->andReturn($this->stub('model.guarded.stub'));

        $this->filesystem->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));

        $this->filesystem->shouldReceive('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname('app/Models/Comment.php'))
            ->andReturnTrue();

        $this->filesystem->expects('put')
            ->with('app/Models/Comment.php', $this->fixture('models/model-guarded.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/model-guarded.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Models/Comment.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_models_with_namespaces_correctly()
    {
        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
        $this->filesystem->expects('stub')
            ->times(4)
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->filesystem->expects('stub')
            ->times(4)
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->filesystem->expects('stub')
            ->times(4)
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->times(4)
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/QuestionType.php', \Mockery::type('string'));
        $this->filesystem->expects('put')
            ->with('app/Models/Appointment/AppointmentType.php', \Mockery::type('string'));
        $this->filesystem->expects('put')
            ->with('app/Models/Screening/Report.php', \Mockery::type('string'));
        $this->filesystem->expects('put')
            ->with('app/Models/Screening/ScreeningQuestion.php', $this->fixture('models/nested-models.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/nested-models.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([
            'created' => [
                'app/Models/QuestionType.php',
                'app/Models/Appointment/AppointmentType.php',
                'app/Models/Screening/Report.php',
                'app/Models/Screening/ScreeningQuestion.php',
            ],
        ], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_models_with_custom_namespace_correctly()
    {
        $this->app['config']->set('blueprint.models_namespace', 'MyCustom');

        $definition = 'drafts/custom-models-namespace.yaml';
        $path = 'app/MyCustom/Tag.php';
        $model = 'models/custom-models-namespace.php';

        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
        $this->filesystem->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->filesystem->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->filesystem->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));

        $this->filesystem->expects('exists')
            ->with('app/MyCustom')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($model));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_models_with_custom_pivot_table_name()
    {
        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
        $this->filesystem->expects('stub')
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->filesystem->expects('stub')
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->filesystem->expects('stub')
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));
        $this->filesystem->expects('stub')
            ->with('model.hidden.stub')
            ->andReturn($this->stub('model.hidden.stub'));

        $this->filesystem->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with('app/Models/User.php', $this->fixture('models/custom-pivot-table-name.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/custom-pivot-table-name.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Models/User.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_models_with_custom_pivot()
    {
        $this->filesystem->expects('stub')
            ->with('model.class.stub')
            ->andReturn($this->stub('model.class.stub'));
        $this->filesystem->expects('stub')
            ->times(3)
            ->with('model.fillable.stub')
            ->andReturn($this->stub('model.fillable.stub'));
        $this->filesystem->expects('stub')
            ->times(3)
            ->with('model.casts.stub')
            ->andReturn($this->stub('model.casts.stub'));
        $this->filesystem->expects('stub')
            ->times(3)
            ->with('model.method.stub')
            ->andReturn($this->stub('model.method.stub'));
        $this->filesystem->expects('stub')
            ->with('model.table.stub')
            ->andReturn($this->stub('model.table.stub'));
        $this->filesystem->expects('stub')
            ->with('model.incrementing.stub')
            ->andReturn($this->stub('model.incrementing.stub'));

        $this->filesystem->expects('exists')
            ->times(3)
            ->with('app/Models')
            ->andReturnTrue();

        $this->filesystem->expects('put')
            ->with('app/Models/User.php', $this->fixture('models/custom-pivot-user.php'));
        $this->filesystem->expects('put')
            ->with('app/Models/Team.php', $this->fixture('models/custom-pivot-team.php'));
        $this->filesystem->expects('put')
            ->with('app/Models/Membership.php', $this->fixture('models/custom-pivot-membership.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/custom-pivot.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Models/User.php', 'app/Models/Team.php', 'app/Models/Membership.php']], $this->subject->output($tree));
    }

    public function modelTreeDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'app/Models/Post.php', 'models/readme-example.php'],
            ['drafts/with-timezones.yaml', 'app/Models/Comment.php', 'models/comment.php'],
            ['drafts/soft-deletes.yaml', 'app/Models/Comment.php', 'models/soft-deletes.php'],
            ['drafts/relationships.yaml', 'app/Models/Comment.php', 'models/relationships.php'],
            ['drafts/unconventional.yaml', 'app/Models/Team.php', 'models/unconventional.php'],
            ['drafts/nested-components.yaml', 'app/Models/Admin/User.php', 'models/nested-components.php'],
            ['drafts/resource-statements.yaml', 'app/Models/User.php', 'models/resource-statements.php'],
            ['drafts/all-column-types.yaml', 'app/Models/AllType.php', 'models/all-column-types.php'],
            ['drafts/alias-relationships.yaml', 'app/Models/Salesman.php', 'models/alias-relationships.php'],
            ['drafts/uuid-shorthand-invalid-relationship.yaml', 'app/Models/AgeCohort.php', 'models/uuid-shorthand-invalid-relationship.php'],
            ['drafts/model-with-meta.yaml', 'app/Models/Post.php', 'models/model-with-meta.php'],
        ];
    }

    public function docBlockModelsDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'app/Models/Post.php', 'models/readme-example-phpdoc.php'],
            ['drafts/soft-deletes.yaml', 'app/Models/Comment.php', 'models/soft-deletes-phpdoc.php'],
            ['drafts/relationships.yaml', 'app/Models/Comment.php', 'models/relationships-phpdoc.php'],
            ['drafts/disable-auto-columns.yaml', 'app/Models/State.php', 'models/disable-auto-columns-phpdoc.php'],
            ['drafts/foreign-key-shorthand.yaml', 'app/Models/Comment.php', 'models/foreign-key-shorthand-phpdoc.php'],
        ];
    }
}
