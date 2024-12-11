<?php

namespace Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\ComponentGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see ComponentGenerator
 */
final class ComponentGeneratorTest extends TestCase
{
    private $blueprint;

    /** @var ComponentGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ComponentGenerator($this->filesystem);

        $this->blueprint = new Blueprint;
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer);
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ComponentLexer(new StatementLexer));
        $this->blueprint->registerGenerator($this->subject);
    }

    #[Test]
    public function output_writes_nothing_for_empty_tree(): void
    {
        $this->filesystem->expects('stub')
            ->with('livewire.class.stub')
            ->andReturn($this->stub('livewire.class.stub'));

        $this->assertEquals([], $this->subject->output(new Tree(['components' => []])));

        $this->filesystem->shouldNotHaveReceived('put');
    }

    #[Test]
    #[DataProvider('componentTreeDataProvider')]
    public function output_generates_components_for_tree($definition, $paths, $component): void
    {
        $this->filesystem->expects('stub')
            ->with('livewire.class.stub')
            ->andReturn($this->stub('livewire.class.stub'));
        $this->filesystem->expects('stub')
            ->with('livewire.method.stub')
            ->andReturn($this->stub('livewire.method.stub'));

        $view = $this->stub('livewire.view.stub');
        $this->filesystem->expects('stub')
            ->with('livewire.view.stub')
            ->andReturn($view);

        $this->filesystem->expects('exists')
            ->with(dirname($paths[0]))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($paths[0], $this->fixture($component));
        $this->filesystem->expects('exists')
            ->with(dirname($paths[1]))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($paths[1], $view);

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => $paths], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_components_with_models_using_custom_namespace(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.');

        $definition = 'drafts/custom-models-namespace.yaml';
        $path = 'app/Http/Controllers/TagController.php';
        $component = 'components/custom-models-namespace.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->filesystem->expects('stub')
            ->with('livewire.class.stub')
            ->andReturn($this->stub('livewire.class.stub'));
        $this->filesystem->expects('stub')
            ->with('livewire.method.stub')
            ->andReturn($this->stub('livewire.method.stub'));

        $this->filesystem->expects('exists')
            ->with(dirname($path))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture($component));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    #[Test]
    public function output_respects_configuration(): void
    {
        $this->markTestIncomplete('This test has not been implemented yet.');

        $this->app['config']->set('blueprint.app_path', 'src/path');
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.components_namespace', 'Other\\Http');

        $this->filesystem->expects('stub')
            ->with('livewire.class.stub')
            ->andReturn($this->stub('livewire.class.stub'));
        $this->filesystem->expects('stub')
            ->with('livewire.method.stub')
            ->andReturn($this->stub('livewire.method.stub'));

        $this->filesystem->expects('exists')
            ->with('src/path/Other/Http')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('src/path/Other/Http', 0755, true);
        $this->filesystem->expects('put')
            ->with('src/path/Other/Http/UserController.php', $this->fixture('components/component-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/simple-component.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Other/Http/UserController.php']], $this->subject->output($tree));
    }

    public static function componentTreeDataProvider(): array
    {
        return [
            ['drafts/livewire-simple.yaml', ['app/Livewire/SimpleComponent.php', 'resources/views/livewire/simple-component.blade.php'], 'components/simple.php'],
            ['drafts/livewire-with-properties.yaml', ['app/Livewire/UpdateProfile.php', 'resources/views/livewire/update-profile.blade.php'], 'components/with-properties.php'],
            ['drafts/livewire-properties-statements.yaml', ['app/Livewire/UpdateProfile.php', 'resources/views/livewire/update-profile.blade.php'], 'components/properties-statements.php'],
        ];
    }
}
