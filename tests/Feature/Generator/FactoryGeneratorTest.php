<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\FactoryGenerator;
use Tests\TestCase;

/**
 * @see FactoryGenerator
 */
class FactoryGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var FactoryGenerator */
    private $subject;

    protected function setUp(): void
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
        $this->files->expects('stub')
            ->with('factory.stub')
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
        $this->files->expects('stub')
            ->with('factory.stub')
            ->andReturn(file_get_contents('stubs/factory.stub'));

        $this->files->expects('put')
            ->with($path, $this->fixture($migration));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_respects_configuration()
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->files->expects('stub')
            ->with('factory.stub')
            ->andReturn(file_get_contents('stubs/factory.stub'));

        $this->files->expects('put')
            ->with('database/factories/PostFactory.php', $this->fixture('factories/post-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/post.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/factories/PostFactory.php']], $this->subject->output($tree));
    }

    public function modelTreeDataProvider()
    {
        return [
            ['definitions/phone.bp', 'database/factories/PhoneFactory.php', 'factories/phone.php'],
            ['definitions/post.bp', 'database/factories/PostFactory.php', 'factories/post.php'],
            ['definitions/team.bp', 'database/factories/TeamFactory.php', 'factories/team.php'],
            ['definitions/unconventional.bp', 'database/factories/TeamFactory.php', 'factories/unconventional.php'],
            ['definitions/nested-components.bp', 'database/factories/Admin/UserFactory.php', 'factories/nested-components.php'],
            ['definitions/model-modifiers.bp', 'database/factories/ModifierFactory.php', 'factories/model-modifiers.php']
        ];
    }
}
