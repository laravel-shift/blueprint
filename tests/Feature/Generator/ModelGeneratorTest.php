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
    public function output_writes_nothing_for_empty_tree()
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
    public function output_writes_migration_for_model_tree($definition, $path, $model)
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

        if ($definition === 'definitions/readme-example.bp') {
            $this->files->expects('stub')
                ->with('model/dates.stub')
                ->andReturn(file_get_contents('stubs/model/dates.stub'));
        }

        $this->files->shouldReceive('stub')
            ->with('model/method.stub')
            ->andReturn(file_get_contents('stubs/model/method.stub'));

        $this->files->expects('put')
            ->with($path, $this->fixture($model));

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

        $this->files->expects('put')
            ->with('src/path/Models/Comment.php', $this->fixture('models/model-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/relationships.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Models/Comment.php']], $this->subject->output($tree));
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
}
