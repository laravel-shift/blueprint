<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\SeederGenerator;
use Blueprint\Tree;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

/**
 * @see SeederGenerator
 */
class SeederGeneratorTest extends TestCase
{
    /**
     * @var Blueprint
     */
    private $blueprint;

    /** @var SeederGenerator */
    private $subject;

    protected $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seederStub = version_compare(App::version(), '8.0.0', '>=') ? 'seeder.stub' : 'seeder.no-factory.stub';
        $this->subject = new SeederGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerLexer(new \Blueprint\Lexers\SeederLexer());
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_generates_nothing_for_empty_tree()
    {
        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['seeders' => []])));
    }

    /**
     * @test
     */
    public function output_generates_seeders()
    {
        $this->filesystem->expects('stub')
            ->with($this->seederStub)
            ->andReturn($this->stub($this->seederStub));

        $this->filesystem->expects('put')
            ->with('database/seeders/PostSeeder.php', $this->fixture('seeders/PostSeeder.php'));
        $this->filesystem->expects('put')
            ->with('database/seeders/CommentSeeder.php', $this->fixture('seeders/CommentSeeder.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/seeders.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/seeders/PostSeeder.php', 'database/seeders/CommentSeeder.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_using_return_types()
    {
        $this->app['config']->set('blueprint.use_return_types', true);

        $this->filesystem->expects('stub')
            ->with($this->seederStub)
            ->andReturn($this->stub($this->seederStub));

        $this->filesystem->expects('put')
            ->with('database/seeders/PostSeeder.php', $this->fixture('seeders/PostSeeder-return-type-declarations.php'));
        $this->filesystem->expects('put')
            ->with('database/seeders/CommentSeeder.php', $this->fixture('seeders/CommentSeeder-return-type-declarations.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/seeders.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/seeders/PostSeeder.php', 'database/seeders/CommentSeeder.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_seeders_from_traced_models()
    {
        $this->filesystem->expects('stub')
            ->with($this->seederStub)
            ->andReturn($this->stub($this->seederStub));

        $this->filesystem->expects('put')
            ->with('database/seeders/PostSeeder.php', $this->fixture('seeders/PostSeeder.php'));
        $this->filesystem->expects('put')
            ->with('database/seeders/CommentSeeder.php', $this->fixture('seeders/CommentSeeder.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/seeders.yaml'));
        $tree = $this->blueprint->analyze($tokens)->toArray();
        $tree['cache'] = $tree['models'];
        unset($tree['models']);
        $tree = new Tree($tree);

        $this->assertEquals(['created' => ['database/seeders/PostSeeder.php', 'database/seeders/CommentSeeder.php']], $this->subject->output($tree));
    }
}
