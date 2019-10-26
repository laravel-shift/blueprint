<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\ModelGenerator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ModelGeneratorTest extends TestCase
{
    private $blueprint;

    private $file;

    protected function setUp()
    {
        parent::setUp();

        $this->file = \Mockery::mock();
        File::swap($this->file);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerGenerator(new ModelGenerator());
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->file->expects('get')
            ->with('stubs/model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));

        $this->file->shouldNotHaveReceived('put');

        $this->blueprint->generate(['models' => []]);
    }

    /**
     * @test
     * @dataProvider modelTreeDataProvider
     */
    public function output_writes_migration_for_model_tree($definition, $path, $model)
    {
        $this->file->expects('get')
            ->with('stubs/model/class.stub')
            ->andReturn(file_get_contents('stubs/model/class.stub'));

        $this->file->expects('get')
            ->with('stubs/model/fillable.stub')
            ->andReturn(file_get_contents('stubs/model/fillable.stub'));

        $this->file->expects('get')
            ->with('stubs/model/casts.stub')
            ->andReturn(file_get_contents('stubs/model/casts.stub'));

        $this->file->expects('get')
            ->with('stubs/model/dates.stub')
            ->andReturn(file_get_contents('stubs/model/dates.stub'));

        $this->file->expects('put')
            ->with($path, $this->fixture($model));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->blueprint->generate($tree);
    }


    public function modelTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'build/Post.php', 'models/readme-example.php'],
            // TODO: relationships
        ];
    }
}