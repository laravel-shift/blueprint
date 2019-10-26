<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\FactoryGenerator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FactoryGeneratorTest extends TestCase
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
        $this->blueprint->registerGenerator(new FactoryGenerator());
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->file->expects('get')
            ->with('stubs/factory.stub')
            ->andReturn(file_get_contents('stubs/factory.stub'));

        $this->file->shouldNotHaveReceived('put');

        $this->blueprint->generate(['models' => []]);
    }

    /**
     * @test
     * @dataProvider modelTreeDataProvider
     */
    public function output_writes_migration_for_model_tree($definition, $path, $migration)
    {
        $this->file->expects('get')
            ->with('stubs/factory.stub')
            ->andReturn(file_get_contents('stubs/factory.stub'));

        $this->file->expects('put')
            ->with($path, $this->fixture($migration));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->blueprint->generate($tree);
    }


    public function modelTreeDataProvider()
    {
        return [
            ['definitions/post.bp', 'build/PostFactory.php', 'factories/post.php']
        ];
    }
}