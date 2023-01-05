<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\RouteGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use Tests\TestCase;

/**
 * @see RouteGenerator
 */
class RouteGeneratorTest extends TestCase
{
    protected $files;

    private $blueprint;

    /** @var RouteGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RouteGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_generates_nothing_for_empty_tree()
    {
        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));

        $this->files->shouldNotHaveReceived('append');
    }

    /**
     * @test
     *
     * @dataProvider controllerTreeDataProvider
     */
    public function output_generates_web_routes($definition, $routes)
    {
        $path = 'routes/web.php';
        $this->filesystem->expects('append')
            ->with($path, $this->fixture($routes));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => [$path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_api_routes()
    {
        $this->filesystem->expects('append')
            ->with('routes/api.php', $this->fixture('routes/api-routes.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/api-routes-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => ['routes/api.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_routes_with_plural_slug()
    {
        $this->app['config']->set('blueprint.plural_routes', true);

        $this->filesystem->expects('append')
            ->with('routes/web.php', $this->fixture('routes/readme-example-plural.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => ['routes/web.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_api_routes_with_plural_slug()
    {
        $this->app['config']->set('blueprint.plural_routes', true);

        $this->filesystem->expects('append')
            ->with('routes/api.php', $this->fixture('routes/api-routes-plural.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/api-routes-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => ['routes/api.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_routes_for_mixed_resources()
    {
        $this->filesystem->expects('append')
            ->with('routes/api.php', $this->fixture('routes/multiple-resource-controllers-api.php'));
        $this->filesystem->expects('append')
            ->with('routes/web.php', $this->fixture('routes/multiple-resource-controllers-web.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/multiple-resource-controllers.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => ['routes/api.php', 'routes/web.php']], $this->subject->output($tree));
    }

    public function controllerTreeDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'routes/readme-example.php'],
            ['drafts/routes-mixed.yaml', 'routes/routes-mixed.php'],
            ['drafts/cruddy.yaml', 'routes/cruddy.php'],
            ['drafts/non-cruddy.yaml', 'routes/non-cruddy.php'],
            ['drafts/respond-statements.yaml', 'routes/respond-statements.php'],
            ['drafts/invokable-controller.yaml', 'routes/invokable-controller.php'],
            ['drafts/invokable-controller-shorthand.yaml', 'routes/invokable-controller.php'],
        ];
    }
}
