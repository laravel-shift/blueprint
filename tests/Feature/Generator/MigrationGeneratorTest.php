<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\MigrationGenerator;
use Carbon\Carbon;
use Tests\TestCase;

class MigrationGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var MigrationGenerator */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new MigrationGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->files->expects('get')
            ->with('stubs/migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['models' => []]));
    }

    /**
     * @test
     * @dataProvider modelTreeDataProvider
     */
    public function output_writes_migration_for_model_tree($definition, $path, $migration)
    {
        $this->files->expects('get')
            ->with('stubs/migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = str_replace('timestamp', $now->format('Y_m_d_His'), $path);

        $this->files->expects('put')
            ->with($timestamp_path, $this->fixture($migration));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$timestamp_path]], $this->subject->output($tree));
    }


    public function modelTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'database/migrations/timestamp_create_posts_table.php', 'migrations/readme-example.php'],
            ['definitions/model-identities.bp', 'database/migrations/timestamp_create_relationships_table.php', 'migrations/identity-columns.php'],
            ['definitions/model-modifiers.bp', 'database/migrations/timestamp_create_modifiers_table.php', 'migrations/modifiers.php'],
        ];
    }
}
