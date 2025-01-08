<?php

namespace Tests\Feature\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\InertiaPageGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see InertiaPageGenerator
 */
final class InertiaPageGeneratorTest extends TestCase
{
    private $blueprint;

    protected $files;

    /** @var InertiaPageGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new InertiaPageGenerator($this->files);

        $this->blueprint = new Blueprint;
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer));
        $this->blueprint->registerGenerator($this->subject);
    }

    #[Test]
    public function output_writes_nothing_for_empty_tree(): void
    {
        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    #[Test]
    public function output_writes_nothing_without_inertia_statements(): void
    {
        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/controllers-only.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    #[Test]
    public function output_writes_nothing_when_package_json_is_missing(): void
    {
        $this->filesystem->expects('exists')
            ->with(base_path('package.json'))
            ->andReturnFalse();
        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/controllers-only.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    #[Test]
    public function output_writes_nothing_when_adapter_is_not_found(): void
    {
        $this->filesystem->expects('exists')
            ->with(base_path('package.json'))
            ->andReturnTrue();
        $this->filesystem->expects('get')
            ->with(base_path('package.json'))
            ->andReturn('');
        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/controllers-only.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    #[Test]
    #[DataProvider('inertiaAdaptersDataProvider')]
    public function output_writes_pages_for_inertia_statements($framework, $dependencies, $path, $extension): void
    {
        $this->filesystem->expects('exists')
            ->with(base_path('package.json'))
            ->andReturnTrue();
        $this->filesystem->expects('get')
            ->with(base_path('package.json'))
            ->andReturn($dependencies);
        $this->filesystem->expects('stub')
            ->with("inertia.$framework.stub")
            ->andReturn($this->stub("inertia.$framework.stub"));
        $this->filesystem->expects('exists')
            ->with($path)
            ->andReturnFalse();
        $this->filesystem->expects('put')
            ->with($path, $this->fixture('inertia-pages/customer-show' . $extension));

        $tokens = $this->blueprint->parse($this->fixture('drafts/inertia-render.yaml'));
        $tree = $this->blueprint->analyze($tokens);
        $output = $this->subject->output($tree);

        $this->assertContains(
            $path,
            $output['created'],
        );
    }

    #[Test]
    #[DataProvider('inertiaAdaptersDataProvider')]
    public function it_outputs_skipped_pages($framework, $dependencies, $path): void
    {
        $this->filesystem->expects('exists')
            ->with(base_path('package.json'))
            ->andReturnTrue();
        $this->filesystem->expects('get')
            ->with(base_path('package.json'))
            ->andReturn($dependencies);
        $this->filesystem->expects('stub')
            ->with("inertia.$framework.stub")
            ->andReturn($this->stub("inertia.$framework.stub"));
        $this->filesystem->expects('exists')
            ->with($path)
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->never();

        $tokens = $this->blueprint->parse($this->fixture('drafts/inertia-render.yaml'));
        $tree = $this->blueprint->analyze($tokens);
        $ouput = $this->subject->output($tree);

        $this->assertEquals([
            'skipped' => [
                $path,
            ],
        ], $ouput);
    }

    public static function inertiaAdaptersDataProvider(): array
    {
        return [
            ['vue', '"@inertiajs/vue3": "^2.0.0"', 'resources/js/Pages/Customer/Show.vue', '.vue'],
            ['react', '"@inertiajs/react": "^2.0.0"', 'resources/js/Pages/Customer/Show.jsx', '.jsx'],
            ['svelte', '"@inertiajs/svelte": "^2.0.0"', 'resources/js/Pages/Customer/Show.svelte', '.svelte'],
        ];
    }
}
