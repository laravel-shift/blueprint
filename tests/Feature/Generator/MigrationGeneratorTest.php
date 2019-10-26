<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\MigrationGenerator;
use Blueprint\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MigrationGeneratorTest extends TestCase
{
    private $blueprint;

    private $file;

    protected function setUp()
    {
        parent::setUp();

        $this->file = \Mockery::mock();
        File::swap($this->file);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerGenerator(new MigrationGenerator());
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->file->expects('get')
            ->with('stubs/migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $this->file->shouldNotHaveReceived('put');

        $this->blueprint->generate(['models' => []]);
    }

    /**
     * @test
     * @dataProvider modelTreeDataProvider
     */
    public function output_writes_migration_for_model_tree($definition, $path, $migration)
    {
        $this->file->expects('get')
            ->with('stubs/migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = str_replace('timestamp', $now->format('Y_m_d_His'), $path);

        $this->file->expects('put')
            ->with($timestamp_path, $this->fixture($migration));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);
        $this->blueprint->generate($tree);
    }


    public function modelTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'build/timestamp_create_posts_table.php', 'migrations/readme-example.php'],
            ['definitions/model-identities.bp', 'build/timestamp_create_relationships_table.php', 'migrations/identity-columns.php'],
            ['definitions/model-modifiers.bp', 'build/timestamp_create_modifiers_table.php', 'migrations/modifiers.php'],
            // TODO: optimizations like nullableTimestamp, unsignedInteger, etc
        ];
    }
}