<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\SeederGenerator;
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

        $this->subject = new SeederGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\SeederLexer());
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_generates_nothing_for_empty_tree()
    {
//        $this->files->expects('stub')
//            ->with('migration.stub')
//            ->andReturn(file_get_contents('stubs/migration.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['seeders' => []]));
    }

    /**
     * @test
     */
    public function output_generates_seeder()
    {
        $this->files->expects('stub')
            ->with('seeder.stub')
            ->andReturn(file_get_contents('stubs/seeder.stub'));

        $this->files->expects('put')
            ->with('database/seeds/PostSeeder.php', $this->fixture('seeders/PostSeeder.php'));
        $this->files->expects('put')
            ->with('database/seeds/CommentSeeder.php', $this->fixture('seeders/CommentSeeder.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/seeders.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['database/seeds/PostSeeder.php', 'database/seeds/CommentSeeder.php']], $this->subject->output($tree));
    }
}
