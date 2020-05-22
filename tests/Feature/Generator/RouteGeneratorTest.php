<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\RouteGenerator;
use Blueprint\Lexers\StatementLexer;
use Tests\TestCase;

/**
 * @see RouteGenerator
 */
class RouteGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var RouteGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
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
        $this->files->shouldNotHaveReceived('append');

        $this->assertEquals([], $this->subject->output(['controllers' => []]));
    }

    /**
     * @test
     * @dataProvider controllerTreeDataProvider
     */
    public function output_generates_web_routes($definition, $routes)
    {
        $path = 'routes/web.php';
        $this->files->expects('append')
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
        $this->files->expects('append')
            ->with('routes/api.php', $this->fixture('routes/api-routes.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/api-routes-example.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => ['routes/api.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_routes_for_mixed_resources()
    {
        $this->files->expects('append')
            ->with('routes/api.php', $this->fixture('routes/multiple-resource-controllers-api.php'));
        $this->files->expects('append')
            ->with('routes/web.php', $this->fixture('routes/multiple-resource-controllers-web.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/multiple-resource-controllers.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => ['routes/api.php', 'routes/web.php']], $this->subject->output($tree));
    }

    public function controllerTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'routes/readme-example.php'],
            ['definitions/cruddy.bp', 'routes/cruddy.php'],
            ['definitions/non-cruddy.bp', 'routes/non-cruddy.php'],
            ['definitions/respond-statements.bp', 'routes/respond-statements.php'],
        ];
    }
}
