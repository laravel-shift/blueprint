<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\MigrationGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
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
    public function output_writes_migration_for_foreign_shorthand()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('put')
            ->with($timestamp_path, $this->fixture('migrations/foreign-key-shorthand.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/foreign-key-shorthand.bp'));
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

    /**
     * @test
     */
    public function output_uses_proper_data_type_for_id_columns_in_laravel6()
    {
        $app = \Mockery::mock();
        $app->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');
        App::swap($app);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_relationships_table.php');

        $this->files->expects('put')
            ->with($timestamp_path, $this->fixture('migrations/identity-columns-big-increments.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/model-identities.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$timestamp_path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_constraints_for_unconventional_foreign_reference_migration()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/relationships-constraints.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/relationships.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_constraints_for_unconventional_foreign_reference_migration_laravel6()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $app = \Mockery::mock();
        $app->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');
        App::swap($app);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/relationships-constraints-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/relationships.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_creates_pivot_table_migration()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/belongs-to-many.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_creates_pivot_table_migration_laravel6()
    {
        $app = \Mockery::mock();
        $app->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');
        App::swap($app);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many-laravel6.php'));

        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/belongs-to-many.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_creates_constraints_for_pivot_table_migration()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many-key-constraints.php'));

        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot-key-constraints.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/belongs-to-many.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_creates_constraints_for_pivot_table_migration_laravel6()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $app = \Mockery::mock();
        $app->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');
        App::swap($app);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many-key-constraints-laravel6.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot-key-constraints-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/belongs-to-many.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_does_not_duplicate_pivot_table_migration()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $company_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_companies_table.php');
        $people_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_people_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_company_person_table.php');

        $this->files->expects('put')
            ->with($company_migration, $this->fixture('migrations/belongs-to-many-duplicated-company.php'));
        $this->files->expects('put')
            ->with($people_migration, $this->fixture('migrations/belongs-to-many-duplicated-people.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-duplicated-pivot.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/belongs-to-many-duplicated-pivot.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$company_migration, $people_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_does_not_duplicate_pivot_table_migration_laravel6()
    {
        $app = \Mockery::mock();
        $app->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');
        App::swap($app);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $company_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_companies_table.php');
        $people_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_people_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_company_person_table.php');

        $this->files->expects('put')
            ->with($company_migration, $this->fixture('migrations/belongs-to-many-duplicated-company-laravel6.php'));
        $this->files->expects('put')
            ->with($people_migration, $this->fixture('migrations/belongs-to-many-duplicated-people-laravel6.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-duplicated-pivot-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/belongs-to-many-duplicated-pivot.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$company_migration, $people_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_creates_pivot_table_migration_with_custom_name()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_test_table.php');

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/custom-pivot-table-name-user.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/custom-pivot-table-name-test.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/custom-pivot-table-name.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_creates_pivot_table_migration_with_custom_name_laravel6()
    {
        $app = \Mockery::mock();
        $app->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');
        App::swap($app);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_test_table.php');

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/custom-pivot-table-name-user-laravel6.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/custom-pivot-table-name-test-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/custom-pivot-table-name.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_foreign_keys_with_nullable_chained_correctly()
    {
        $this->app->config->set('blueprint.on_delete', 'null');

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_carts_table.php');

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/nullable-chaining.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/nullable-chaining.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_foreign_keys_with_nullable_chained_correctly_laravel6()
    {
        $this->app->config->set('blueprint.on_delete', 'null');

        $app = \Mockery::mock();
        $app->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');
        App::swap($app);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_carts_table.php');

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/nullable-chaining-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/nullable-chaining.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_foreign_keys_with_on_delete()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/foreign-key-on-delete.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/foreign-key-on-delete.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_foreign_keys_with_on_delete_laravel6()
    {
        $app = \Mockery::mock();
        $app->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');
        App::swap($app);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/foreign-key-on-delete-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/foreign-key-on-delete.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_works_with_polymorphic_relationships()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $post_migration = str_replace('timestamp', $now->copy()->subSeconds(2)->format('Y_m_d_His'), 'database/migrations/timestamp_create_posts_table.php');
        $user_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $image_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_images_table.php');

        $this->files->expects('put')
            ->with($post_migration, $this->fixture('migrations/polymorphic_relationships_posts_table.php'));
        $this->files->expects('put')
            ->with($user_migration, $this->fixture('migrations/polymorphic_relationships_users_table.php'));
        $this->files->expects('put')
            ->with($image_migration, $this->fixture('migrations/polymorphic_relationships_images_table.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/polymorphic-relationships.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$post_migration, $user_migration, $image_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_works_with_polymorphic_relationships_laravel6()
    {
        $app = \Mockery::mock();
        $app->shouldReceive('version')
            ->withNoArgs()
            ->andReturn('6.0.0');
        App::swap($app);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn(file_get_contents('stubs/migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $post_migration = str_replace('timestamp', $now->copy()->subSeconds(2)->format('Y_m_d_His'), 'database/migrations/timestamp_create_posts_table.php');
        $user_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $image_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_images_table.php');

        $this->files->expects('put')
            ->with($post_migration, $this->fixture('migrations/polymorphic_relationships_posts_table_laravel6.php'));
        $this->files->expects('put')
            ->with($user_migration, $this->fixture('migrations/polymorphic_relationships_users_table_laravel6.php'));
        $this->files->expects('put')
            ->with($image_migration, $this->fixture('migrations/polymorphic_relationships_images_table_laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/polymorphic-relationships.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$post_migration, $user_migration, $image_migration]], $this->subject->output($tree));
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
            ['definitions/optimize.bp', 'database/migrations/timestamp_create_optimizes_table.php', 'migrations/optimize.php'],
            ['definitions/model-key-constraints.bp', 'database/migrations/timestamp_create_orders_table.php', 'migrations/model-key-constraints.php'],
            ['definitions/disable-auto-columns.bp', 'database/migrations/timestamp_create_states_table.php', 'migrations/disable-auto-columns.php'],
            ['definitions/uuid-shorthand.bp', 'database/migrations/timestamp_create_people_table.php', 'migrations/uuid-shorthand.php'],
            ['definitions/unconventional-foreign-key.bp', 'database/migrations/timestamp_create_states_table.php', 'migrations/unconventional-foreign-key.php'],
            ['definitions/resource-statements.bp', 'database/migrations/timestamp_create_users_table.php', 'migrations/resource-statements.php'],
        ];
    }
}
