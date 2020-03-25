<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\MigrationGenerator;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * @see MigrationGenerator
 */
class MigrationGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var MigrationGenerator */
    private $subject;

    protected function setUp(): void
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
        $this->files->expects('stub')
            ->with('migration.stub')
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
        $this->files->expects('stub')
            ->with('migration.stub')
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

    /**
     * @test
     */
    public function output_uses_past_timestamp_for_multiple_migrations()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $post_path = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_posts_table.php');
        $comment_path = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('put')
            ->with($post_path, $this->fixture('migrations/posts.php'));
        $this->files->expects('put')
            ->with($comment_path, $this->fixture('migrations/comments.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/multiple-models.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$post_path, $comment_path]], $this->subject->output($tree));
    }

    public function modelTreeDataProvider()
    {
        return [
            ['definitions/readme-example.bp', 'database/migrations/timestamp_create_posts_table.php', 'migrations/readme-example.php'],
            ['definitions/model-identities.bp', 'database/migrations/timestamp_create_relationships_table.php', 'migrations/identity-columns.php'],
            ['definitions/model-modifiers.bp', 'database/migrations/timestamp_create_modifiers_table.php', 'migrations/model-modifiers.php'],
            ['definitions/soft-deletes.bp', 'database/migrations/timestamp_create_comments_table.php', 'migrations/soft-deletes.php'],
            ['definitions/with-timezones.bp', 'database/migrations/timestamp_create_comments_table.php', 'migrations/with-timezones.php'],
            ['definitions/relationships.bp', 'database/migrations/timestamp_create_comments_table.php', 'migrations/relationships.php'],
            ['definitions/unconventional.bp', 'database/migrations/timestamp_create_teams_table.php', 'migrations/unconventional.php'],
            ['definitions/model-key-constraints.bp', 'database/migrations/timestamp_create_orders_table.php', 'migrations/model-key-constraints.php'],
        ];
    }
}
