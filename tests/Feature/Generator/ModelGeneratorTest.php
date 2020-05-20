<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\ModelGenerator;
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
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['models' => []]));
    }

    /**
     * @test
     * @dataProvider modelTreeDataProvider
     */
    public function output_generates_models($definition, $path, $model)
    {
        $this->files->expects('stub')
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));

        $this->files->expects('stub')
            ->with('model/fillable.stub')
            ->andReturn(file_get_contents('stubs/model/fillable.stub'));

        if ($definition === 'definitions/nested-components.bp') {
            $this->files->expects('stub')
                ->with('model/hidden.stub')
                ->andReturn(file_get_contents('stubs/model/hidden.stub'));
        }

        $this->files->expects('stub')
            ->with('model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'));

        if ($definition === 'definitions/readme-example.bp') {
            $this->files->expects('stub')
                ->with('model/dates.stub')
                ->andReturn(file_get_contents('stubs/model/dates.stub'));
        }

        $this->files->shouldReceive('stub')
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'));

        $this->files->shouldReceive('stub')
            ->with('model/method-comment.stub')
            ->andReturn(file_get_contents('stubs/model/method-comment.stub'));


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
     */
    public function output_works_for_pascal_case_definition()
    {
        $this->files->expects('stub')
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));
        $this->files->expects('stub')
            ->with('model/fillable.stub')
            ->andReturn(file_get_contents('stubs/model/fillable.stub'))
            ->twice();
        $this->files->expects('stub')
            ->with('model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'))
            ->twice();
        $this->files->expects('stub')
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'))
            ->twice();

        $certificateModel = 'app/Certificate.php';
        $certificateTypeModel = 'app/CertificateType.php';

        $this->files->expects('exists')
            ->with(dirname($certificateModel))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($certificateModel, $this->fixture('models/certificate-pascal-case-example.php'));

        $this->files->expects('exists')
            ->with(dirname($certificateTypeModel))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($certificateTypeModel, $this->fixture('models/certificate-type-pascal-case-example.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/pascal-case.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$certificateModel, $certificateTypeModel]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_relationships()
    {
        $this->files->expects('stub')
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));
        $this->files->expects('stub')
            ->with('model/fillable.stub')
            ->andReturn(file_get_contents('stubs/model/fillable.stub'));
        $this->files->expects('stub')
            ->with('model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'));
        $this->files->expects('stub')
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/Subscription.php', $this->fixture('models/model-relationships.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/model-relationships.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Subscription.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_polymorphic_relationships()
    {
        $this->files->expects('stub')
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));
        $this->files->expects('stub')
            ->times(3)
            ->with('model/fillable.stub')
            ->andReturn(file_get_contents('stubs/model/fillable.stub'));
        $this->files->expects('stub')
            ->times(3)
            ->with('model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'));
        $this->files->expects('stub')
            ->times(3)
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/Post.php', $this->fixture('models/post-polymorphic-relationship.php'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/User.php', $this->fixture('models/user-polymorphic-relationship.php'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/Image.php', $this->fixture('models/image-polymorphic-relationship.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/polymorphic-relationships.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Post.php', 'app/User.php', 'app/Image.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_disabled_auto_columns()
    {
        $this->files->expects('stub')
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));
        $this->files->expects('stub')
            ->with('model/timestamps.stub')
            ->andReturn(file_get_contents('stubs/model/timestamps.stub'));
        $this->files->expects('stub')
            ->with('model/fillable.stub')
            ->andReturn(file_get_contents('stubs/model/fillable.stub'));
        $this->files->expects('stub')
            ->with('model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'));
        $this->files->expects('stub')
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'));

        $this->files->expects('exists')
            ->with('app')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with('app/State.php', $this->fixture('models/disable-auto-columns.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/disable-auto-columns.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/State.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_respects_configuration()
    {
        $this->app['config']->set('blueprint.app_path', 'src/path');
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->files->expects('stub')
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));

        $this->files->expects('stub')
            ->with('model/fillable.stub')
            ->andReturn(file_get_contents('stubs/model/fillable.stub'));

        $this->files->expects('stub')
            ->with('model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'));

        $this->files->expects('stub')
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'));

        $this->files->expects('exists')
            ->with('src/path/Models')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('src/path/Models', 0755, true);
        $this->files->expects('put')
            ->with('src/path/Models/Comment.php', $this->fixture('models/model-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/relationships.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Models/Comment.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @dataProvider docBlockModelsDataProvider
     */
    public function output_generates_phpdoc_for_model($definition, $path, $model)
    {
        $this->app['config']->set('blueprint.generate_phpdocs', true);

        $this->files->expects('stub')
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));

        if ($definition === 'definitions/disable-auto-columns.bp') {
            $this->files->expects('stub')
                ->with('model/timestamps.stub')
                ->andReturn(file_get_contents('stubs/model/timestamps.stub'));
        }

        $this->files->expects('stub')
            ->with('model/fillable.stub')
            ->andReturn(file_get_contents('stubs/model/fillable.stub'));

        $this->files->expects('stub')
            ->with('model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'));

        if ($definition === 'definitions/readme-example.bp') {
            $this->files->expects('stub')
                ->with('model/dates.stub')
                ->andReturn(file_get_contents('stubs/model/dates.stub'));
        }

        $this->files->shouldReceive('stub')
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'));

        $this->files->shouldReceive('stub')
            ->with('model/method-comment.stub')
            ->andReturn(file_get_contents('stubs/model/method-comment.stub'));

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
     */
    public function output_generates_models_with_guarded_property_when_config_option_is_set()
    {
        $this->app['config']->set('blueprint.use_guarded', true);

        $this->files->expects('stub')
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));

        $this->files->expects('stub')
            ->with('model/guarded.stub')
            ->andReturn(file_get_contents('stubs/model/guarded.stub'));

        $this->files->expects('stub')
            ->with('model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'));

        $this->files->shouldReceive('stub')
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'));

        $this->files->expects('exists')
            ->with(dirname('app/Comment.php'))
            ->andReturnTrue();

        $this->files->expects('put')
            ->with('app/Comment.php', $this->fixture('models/model-guarded.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/model-guarded.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Comment.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_models_with_custom_namespace_correctly()
    {
        $definition = 'definitions/custom-models-namespace.bp';
        $path = 'app/Models/Tag.php';
        $model = 'models/custom-models-namespace.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->files->expects('stub')
            ->with('model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));
        $this->files->expects('stub')
            ->with('model/fillable.stub')
            ->andReturn(file_get_contents('stubs/model/fillable.stub'));
        $this->files->expects('stub')
            ->with('model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'));
        $this->files->expects('stub')
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'));

        $this->files->expects('exists')
            ->with('app/Models')
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($path, $this->fixture($model));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    public function modelTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'app/Post.php', 'models/readme-example.php'],
            ['definitions/with-timezones.bp', 'app/Comment.php', 'models/comment.php'],
            ['definitions/soft-deletes.bp', 'app/Comment.php', 'models/soft-deletes.php'],
            ['definitions/relationships.bp', 'app/Comment.php', 'models/relationships.php'],
            ['definitions/unconventional.bp', 'app/Team.php', 'models/unconventional.php'],
            ['definitions/nested-components.bp', 'app/Admin/User.php', 'models/nested-components.php'],
        ];
    }

    public function docBlockModelsDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'app/Post.php', 'models/readme-example-phpdoc.php'],
            ['definitions/soft-deletes.bp', 'app/Comment.php', 'models/soft-deletes-phpdoc.php'],
            ['definitions/relationships.bp', 'app/Comment.php', 'models/relationships-phpdoc.php'],
            ['definitions/disable-auto-columns.bp', 'app/State.php', 'models/disable-auto-columns-phpdoc.php'],
        ];
    }
}
