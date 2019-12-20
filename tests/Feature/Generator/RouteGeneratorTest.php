<?php

namespace Tests\Feature\Generators;

use Tests\TestCase;
use Blueprint\Blueprint;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Generators\RouteGenerator;

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
    public function output_writes_nothing_for_empty_tree()
    {
        $this->files->shouldNotHaveReceived('append');

        $this->assertEquals([], $this->subject->output(['controllers' => []]));
    }

    /**
     * @test
     * @dataProvider controllerTreeDataProvider
     */
    public function output_writes_migration_for_route_tree($definition, $routes)
    {
        $path = 'routes/web.php';
        $this->files->expects('append')
            ->with($path, $this->fixture($routes));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => [$path]], $this->subject->output($tree));
    }

    public function controllerTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'routes/readme-example.php'],
            ['definitions/cruddy.bp', 'routes/cruddy.php'],
            ['definitions/non-cruddy.bp', 'routes/non-cruddy.php'],
        ];
    }
}
