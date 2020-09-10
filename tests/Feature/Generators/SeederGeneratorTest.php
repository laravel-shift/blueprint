<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\SeederGenerator;
use Blueprint\Tree;
use Carbon\Carbon;
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


    private $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
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
        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['seeders' => []])));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_generates_seeders()
    {
        $this->files->expects('stub')
            ->with($this->seederStub)
            ->andReturn($this->stub($this->seederStub));

        $this->files->expects('put')
            ->with('database/seeders/PostSeeder.php', $this->fixture('seeders/PostSeeder.php'));
        $this->files->expects('put')
            ->with('database/seeders/CommentSeeder.php', $this->fixture('seeders/CommentSeeder.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/seeders.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/seeders/PostSeeder.php', 'database/seeders/CommentSeeder.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel7
     */
    public function output_generates_seeders_l7()
    {
        $this->files->expects('stub')
            ->with($this->seederStub)
            ->andReturn($this->stub($this->seederStub));

        $this->files->expects('put')
            ->with('database/seeds/PostSeeder.php', $this->fixture('seeders/no-factory/PostSeeder.php'));
        $this->files->expects('put')
            ->with('database/seeds/CommentSeeder.php', $this->fixture('seeders/no-factory/CommentSeeder.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/seeders.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/seeds/PostSeeder.php', 'database/seeds/CommentSeeder.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_generates_seeders_l6()
    {
        $this->files->expects('stub')
            ->with($this->seederStub)
            ->andReturn($this->stub($this->seederStub));

        $this->files->expects('put')
            ->with('database/seeds/PostSeeder.php', $this->fixture('seeders/no-factory/PostSeeder.php'));
        $this->files->expects('put')
            ->with('database/seeds/CommentSeeder.php', $this->fixture('seeders/no-factory/CommentSeeder.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/seeders.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/seeds/PostSeeder.php', 'database/seeds/CommentSeeder.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel8
     */
    public function output_generates_seeders_from_traced_models()
    {
        $this->files->expects('stub')
            ->with($this->seederStub)
            ->andReturn($this->stub($this->seederStub));

        $this->files->expects('put')
            ->with('database/seeders/PostSeeder.php', $this->fixture('seeders/PostSeeder.php'));
        $this->files->expects('put')
            ->with('database/seeders/CommentSeeder.php', $this->fixture('seeders/CommentSeeder.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/seeders.yaml'));
        $tree = $this->blueprint->analyze($tokens)->toArray();
        $tree['cache'] = $tree['models'];
        unset($tree['models']);
        $tree = new Tree($tree);

        $this->assertEquals(['created' => ['database/seeders/PostSeeder.php', 'database/seeders/CommentSeeder.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel7
     */
    public function output_generates_seeders_from_traced_models_l7()
    {
        $this->files->expects('stub')
            ->with($this->seederStub)
            ->andReturn($this->stub($this->seederStub));

        $this->files->expects('put')
            ->with('database/seeds/PostSeeder.php', $this->fixture('seeders/no-factory/PostSeeder.php'));
        $this->files->expects('put')
            ->with('database/seeds/CommentSeeder.php', $this->fixture('seeders/no-factory/CommentSeeder.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/seeders.yaml'));
        $tree = $this->blueprint->analyze($tokens)->toArray();
        $tree['cache'] = $tree['models'];
        unset($tree['models']);
        $tree = new Tree($tree);

        $this->assertEquals(['created' => ['database/seeds/PostSeeder.php', 'database/seeds/CommentSeeder.php']], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_generates_seeders_from_traced_models_l6()
    {
        $this->files->expects('stub')
            ->with($this->seederStub)
            ->andReturn($this->stub($this->seederStub));

        $this->files->expects('put')
            ->with('database/seeds/PostSeeder.php', $this->fixture('seeders/no-factory/PostSeeder.php'));
        $this->files->expects('put')
            ->with('database/seeds/CommentSeeder.php', $this->fixture('seeders/no-factory/CommentSeeder.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/seeders.yaml'));
        $tree = $this->blueprint->analyze($tokens)->toArray();
        $tree['cache'] = $tree['models'];
        unset($tree['models']);
        $tree = new Tree($tree);

        $this->assertEquals(['created' => ['database/seeds/PostSeeder.php', 'database/seeds/CommentSeeder.php']], $this->subject->output($tree));
    }
}
