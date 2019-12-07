<?php

namespace Tests\Feature\Generators;

use Tests\TestCase;
use Blueprint\Blueprint;
use Blueprint\Generators\ModelGenerator;

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
        $this->files->expects('get')
            ->with('stubs/model/class.stub')
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
        static $iteration = 0;

        $this->files->expects('get')
            ->with('stubs/model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));

        // TODO: remove conditional expectations
        if ($iteration === 0) {
            $this->files->expects('get')
                ->with('stubs/model/fillable.stub')
                ->andReturn(file_get_contents('stubs/model/fillable.stub'));

            $this->files->expects('get')
                ->with('stubs/model/casts.stub')
                ->andReturn(file_get_contents('stubs/model/casts.stub'));

            $this->files->expects('get')
                ->with('stubs/model/dates.stub')
                ->andReturn(file_get_contents('stubs/model/dates.stub'));
        }

        if ($definition === 'definitions/soft-deletes.bp') {
            $this->files->expects('get')
                ->with('stubs/model/method.stub')
                ->andReturn(file_get_contents('stubs/model/method.stub'));
        }

        $this->files->expects('put')
            ->with($path, $this->fixture($model));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
        $iteration++;
    }

    public function modelTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'app/Post.php', 'models/readme-example.php'],
            ['definitions/with-timezones.bp', 'app/Comment.php', 'models/comment.php'],
            ['definitions/soft-deletes.bp', 'app/Comment.php', 'models/soft-deletes.php'],
            ['definitions/relationships.bp', 'app/Comment.php', 'models/relationships.php'],
        ];
    }
}
