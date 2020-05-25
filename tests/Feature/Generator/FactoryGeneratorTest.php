<?php

namespace Tests\Feature\Generators;

use Tests\TestCase;
use Blueprint\Blueprint;
use Blueprint\Generators\FactoryGenerator;

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
    public function output_writes_factory_for_model_tree($definition, $path, $factory)
    {
        $this->files->expects('stub')
            ->with('factory.stub')
            ->andReturn(file_get_contents('stubs/factory.stub'));

        $this->files->expects('exists')
            ->with('database/factories')
            ->andReturnTrue();

        $this->files->expects('put')
            ->with($path, $this->fixture($factory));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_ignores_nullables_if_fake_nullables_configuration_is_set_to_false()
    {
        $this->app['config']->set('blueprint.fake_nullables', false);

        $this->files->expects('stub')
            ->with('factory.stub')
            ->andReturn(file_get_contents('stubs/factory.stub'));

        $this->files->expects('exists')
            ->with('database/factories')
            ->andReturnTrue();

        $this->files->expects('put')
            ->with('database/factories/PostFactory.php', $this->fixture('factories/fake-nullables.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/readme-example-draft.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/factories/PostFactory.php']], $this->subject->output($tree));
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

        $this->files->expects('exists')
            ->with('database/factories')
            ->andReturnTrue();

        $this->files->expects('put')
            ->with('database/factories/PostFactory.php', $this->fixture('factories/post-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/post-draft.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/factories/PostFactory.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_directory_for_nested_components()
    {
        $this->files->expects('stub')
            ->with('factory.stub')
            ->andReturn(file_get_contents('stubs/factory.stub'));

        $this->files->expects('exists')
            ->with('database/factories/Admin')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('database/factories/Admin', 0755, true);

        $this->files->expects('put')
            ->with('database/factories/Admin/UserFactory.php', $this->fixture('factories/nested-components.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/nested-components-draft.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/factories/Admin/UserFactory.php']], $this->subject->output($tree));
    }

    public function modelTreeDataProvider()
    {
        return [
            ['definitions/phone-draft.yaml', 'database/factories/PhoneFactory.php', 'factories/phone.php'],
            ['definitions/post-draft.yaml', 'database/factories/PostFactory.php', 'factories/post.php'],
            ['definitions/team-draft.yaml', 'database/factories/TeamFactory.php', 'factories/team.php'],
            ['definitions/unconventional-draft.yaml', 'database/factories/TeamFactory.php', 'factories/unconventional.php'],
            ['definitions/model-modifiers-draft.yaml', 'database/factories/ModifierFactory.php', 'factories/model-modifiers.php'],
            ['definitions/model-key-constraints-draft.yaml', 'database/factories/OrderFactory.php', 'factories/model-key-constraints.php'],
            ['definitions/unconventional-foreign-key-draft.yaml', 'database/factories/StateFactory.php', 'factories/unconventional-foreign-key.php'],
            ['definitions/foreign-key-shorthand-draft.yaml', 'database/factories/CommentFactory.php', 'factories/foreign-key-shorthand.php'],
            ['definitions/resource-statements-draft.yaml', 'database/factories/UserFactory.php', 'factories/resource-statements.php'],
        ];
    }
}
