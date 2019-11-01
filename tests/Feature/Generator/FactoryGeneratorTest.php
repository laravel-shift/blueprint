<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\FactoryGenerator;
use Tests\TestCase;

class FactoryGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var FactoryGenerator */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new FactoryGenerator($this->files);

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
            ->with('stubs/factory.stub')
            ->andReturn(file_get_contents('stubs/factory.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['models' => []]));
    }

    /**
     * @test
     * @dataProvider modelTreeDataProvider
     */
    public function output_writes_migration_for_model_tree($definition, $path, $migration)
    {
        $this->files->expects('get')
            ->with('stubs/factory.stub')
            ->andReturn(file_get_contents('stubs/factory.stub'));

        $this->files->expects('put')
            ->with($path, $this->fixture($migration));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }


    public function modelTreeDataProvider()
    {
        return [
            ['definitions/post.bp', 'database/factories/PostFactory.php', 'factories/post.php']
        ];
    }
}