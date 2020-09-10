<?php

namespace Tests\Feature\Generators;

use Blueprint\Tree;
use Tests\TestCase;
use Blueprint\Blueprint;
use Blueprint\Generators\FactoryGenerator;
use Illuminate\Support\Facades\App;

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
        $this->factoryStub = version_compare(App::version(), '8.0.0', '>=') ? 'factory.stub' : 'factory.closure.stub';
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
            ->with($this->factoryStub)
            ->andReturn($this->stub($this->factoryStub));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['models' => []])));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     * @dataProvider laravel8ModelTreeDataProvider
     */
    public function output_writes_factory_for_model_tree($definition, $path, $factory)
    {
        $this->files->expects('stub')
            ->with($this->factoryStub)
            ->andReturn($this->stub($this->factoryStub));

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
     * @environment-setup useLaravel7
     * @dataProvider modelTreeDataProvider
     */
    public function output_writes_factory_for_model_tree_l7($definition, $path, $factory)
    {
        $this->files->expects('stub')
            ->with($this->factoryStub)
            ->andReturn($this->stub($this->factoryStub));

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
     * @environment-setup useLaravel6
     * @dataProvider modelTreeDataProvider
     */
    public function output_writes_factory_for_model_tree_l6($definition, $path, $factory)
    {
        $this->files->expects('stub')
            ->with($this->factoryStub)
            ->andReturn($this->stub($this->factoryStub));

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
     * @environment-setup useLaravel8
     */
    public function output_ignores_nullables_if_fake_nullables_configuration_is_set_to_false()
    {
        $this->app['config']->set('blueprint.fake_nullables', false);

        $this->files->expects('stub')
            ->with($this->factoryStub)
            ->andReturn($this->stub($this->factoryStub));

        $this->files->expects('exists')
            ->with('database/factories')
            ->andReturnTrue();

        $this->files->expects('put')
            ->with('database/factories/PostFactory.php', $this->fixture('factories/fake-nullables-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/factories/PostFactory.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_respects_configuration()
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->files->expects('stub')
            ->with($this->factoryStub)
            ->andReturn($this->stub($this->factoryStub));

        $this->files->expects('exists')
            ->with('database/factories')
            ->andReturnTrue();

        $this->files->expects('put')
            ->with('database/factories/PostFactory.php', $this->fixture('factories/post-configured-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/post.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/factories/PostFactory.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_creates_directory_for_nested_components()
    {
        $this->files->expects('stub')
            ->with($this->factoryStub)
            ->andReturn($this->stub($this->factoryStub));

        $this->files->expects('exists')
            ->with('database/factories/Admin')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('database/factories/Admin', 0755, true);

        $this->files->expects('put')
            ->with('database/factories/Admin/UserFactory.php', $this->fixture('factories/nested-components-laravel8.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/nested-components.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/factories/Admin/UserFactory.php']], $this->subject->output($tree));
    }

    public function modelTreeDataProvider()
    {
        return [
            ['drafts/phone.yaml', 'database/factories/PhoneFactory.php', 'factories/phone.php'],
            ['drafts/post.yaml', 'database/factories/PostFactory.php', 'factories/post.php'],
            ['drafts/team.yaml', 'database/factories/TeamFactory.php', 'factories/team.php'],
            ['drafts/unconventional.yaml', 'database/factories/TeamFactory.php', 'factories/unconventional.php'],
            ['drafts/model-modifiers.yaml', 'database/factories/ModifierFactory.php', 'factories/model-modifiers.php'],
            ['drafts/model-key-constraints.yaml', 'database/factories/OrderFactory.php', 'factories/model-key-constraints.php'],
            ['drafts/unconventional-foreign-key.yaml', 'database/factories/StateFactory.php', 'factories/unconventional-foreign-key.php'],
            ['drafts/foreign-key-shorthand.yaml', 'database/factories/CommentFactory.php', 'factories/foreign-key-shorthand.php'],
            ['drafts/resource-statements.yaml', 'database/factories/UserFactory.php', 'factories/resource-statements.php'],
            ['drafts/factory-smallint-and-tinyint.yaml', 'database/factories/ModelFactory.php', 'factories/factory-smallint-and-tinyint.php'],
            ['drafts/all-column-types.yaml', 'database/factories/AllTypeFactory.php', 'factories/all-column-types.php'],
        ];
    }

    public function laravel8ModelTreeDataProvider()
    {
        return [
            ['drafts/phone.yaml', 'database/factories/PhoneFactory.php', 'factories/phone-laravel8.php'],
            ['drafts/post.yaml', 'database/factories/PostFactory.php', 'factories/post-laravel8.php'],
            ['drafts/team.yaml', 'database/factories/TeamFactory.php', 'factories/team-laravel8.php'],
            ['drafts/unconventional.yaml', 'database/factories/TeamFactory.php', 'factories/unconventional-laravel8.php'],
            ['drafts/model-modifiers.yaml', 'database/factories/ModifierFactory.php', 'factories/model-modifiers-laravel8.php'],
            ['drafts/model-key-constraints.yaml', 'database/factories/OrderFactory.php', 'factories/model-key-constraints-laravel8.php'],
            ['drafts/unconventional-foreign-key.yaml', 'database/factories/StateFactory.php', 'factories/unconventional-foreign-key-laravel8.php'],
            ['drafts/foreign-key-shorthand.yaml', 'database/factories/CommentFactory.php', 'factories/foreign-key-shorthand-laravel8.php'],
            ['drafts/resource-statements.yaml', 'database/factories/UserFactory.php', 'factories/resource-statements-laravel8.php'],
            ['drafts/factory-smallint-and-tinyint.yaml', 'database/factories/ModelFactory.php', 'factories/factory-smallint-and-tinyint-laravel8.php'],
            ['drafts/all-column-types.yaml', 'database/factories/AllTypeFactory.php', 'factories/all-column-types-laravel8.php'],
        ];
    }
}
