<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\MigrationGenerator;
use Blueprint\Tree;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Symfony\Component\Finder\SplFileInfo;
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
            ->andReturn($this->stub('migration.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['models' => []])));
    }

    /**
     * @test
     * @dataProvider modelTreeDataProvider
     */
    public function output_writes_migration_for_model_tree($definition, $path, $migration)
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = str_replace('timestamp', $now->format('Y_m_d_His'), $path);

        $this->files->expects('exists')
            ->with($timestamp_path)
            ->andReturn(false);

        $this->files->expects('put')
            ->with($timestamp_path, $this->fixture($migration));

        $tokens = $this->blueprint->parse($this->fixture($definition), $definition !== 'drafts/indexes.yaml');
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$timestamp_path]], $this->subject->output($tree));
    }

    /**
     * @test
     * @dataProvider modelTreeDataProvider
     */
    public function output_updates_migration_for_model_tree($definition, $path, $migration)
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $yday = Carbon::yesterday();

        $yesterday_path = str_replace('timestamp', $yday->format('Y_m_d_His'), $path);

        $this->files->expects('files')
            ->with('database/migrations/')
            ->andReturn([
                new SplFileInfo($yesterday_path, '', ''),
            ]);

        $this->files->expects('exists')
            ->with($yesterday_path)
            ->andReturn(true);

        $this->files->expects('put')
            ->with($yesterday_path, $this->fixture($migration));

        $tokens = $this->blueprint->parse($this->fixture($definition), $definition !== 'drafts/indexes.yaml');
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => [$yesterday_path]], $this->subject->output($tree, true));
    }

    /**
     * @test
     */
    public function output_writes_migration_for_foreign_shorthand()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('exists')->andReturn(false);

        $this->files->expects('put')
            ->with($timestamp_path, $this->fixture('migrations/foreign-key-shorthand.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/foreign-key-shorthand.yaml'));
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
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $post_path = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_posts_table.php');
        $comment_path = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('exists')->twice()->andReturn(false);

        $this->files->expects('put')
            ->with($post_path, $this->fixture('migrations/posts.php'));
        $this->files->expects('put')
            ->with($comment_path, $this->fixture('migrations/comments.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/multiple-models.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$post_path, $comment_path]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_uses_proper_data_type_for_id_columns_in_laravel6()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_relationships_table.php');

        $this->files->expects('exists')->andReturn(false);

        $this->files->expects('put')
            ->with($timestamp_path, $this->fixture('migrations/identity-columns-big-increments.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/model-identities.yaml'));
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
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('exists')->andReturn(false);

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/relationships-constraints.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_creates_constraints_for_unconventional_foreign_reference_migration_laravel6()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('exists')->andReturn(false);

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/relationships-constraints-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/relationships.yaml'));
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
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->files->expects('exists')->twice()->andReturn(false);

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_updates_pivot_table_migration()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $yday = Carbon::yesterday();

        $model_migration = str_replace('timestamp', $yday->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $yday->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->files->expects('files')
            ->with('database/migrations/')
            ->twice()
            ->andReturn([
                new SplFileInfo($model_migration, '', ''),
                new SplFileInfo($pivot_migration, '', ''),
            ]);

        $this->files->expects('exists')->with($model_migration)->andReturn(true);
        $this->files->expects('exists')->with($pivot_migration)->andReturn(true);

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => [$model_migration, $pivot_migration]], $this->subject->output($tree, true));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_also_creates_pivot_table_migration_laravel6()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->files->expects('exists')->twice()->andReturn(false);

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many-laravel6.php'));

        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many.yaml'));
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
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->files->expects('exists')->twice()->andReturn(false);

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many-key-constraints.php'));

        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot-key-constraints.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_also_creates_constraints_for_pivot_table_migration_laravel6()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->files->expects('exists')->twice()->andReturn(false);

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many-key-constraints-laravel6.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot-key-constraints-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many.yaml'));
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
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $company_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_companies_table.php');
        $people_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_people_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_company_person_table.php');

        $this->files->expects('exists')->times(3)->andReturn(false);

        $this->files->expects('put')
            ->with($company_migration, $this->fixture('migrations/belongs-to-many-duplicated-company.php'));
        $this->files->expects('put')
            ->with($people_migration, $this->fixture('migrations/belongs-to-many-duplicated-people.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-duplicated-pivot.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many-duplicated-pivot.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$company_migration, $people_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_does_not_duplicate_pivot_table_migration_laravel6()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $company_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_companies_table.php');
        $people_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_people_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_company_person_table.php');

        $this->files->expects('exists')->times(3)->andReturn(false);

        $this->files->expects('put')
            ->with($company_migration, $this->fixture('migrations/belongs-to-many-duplicated-company-laravel6.php'));
        $this->files->expects('put')
            ->with($people_migration, $this->fixture('migrations/belongs-to-many-duplicated-people-laravel6.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-duplicated-pivot-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many-duplicated-pivot.yaml'));
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
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_test_table.php');

        $this->files->expects('exists')->twice()->andReturn(false);

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/custom-pivot-table-name-user.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/custom-pivot-table-name-test.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/custom-pivot-table-name.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_also_creates_pivot_table_migration_with_custom_name_laravel6()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_test_table.php');

        $this->files->expects('exists')->twice()->andReturn(false);

        $this->files->expects('put')
            ->with($model_migration, $this->fixture('migrations/custom-pivot-table-name-user-laravel6.php'));
        $this->files->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/custom-pivot-table-name-test-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/custom-pivot-table-name.yaml'));
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
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_carts_table.php');

        $this->files->expects('exists')->andReturn(false);

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/nullable-chaining.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/nullable-chaining.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_creates_foreign_keys_with_nullable_chained_correctly_laravel6()
    {
        $this->app->config->set('blueprint.on_delete', 'null');

        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_carts_table.php');

        $this->files->expects('exists')->andReturn(false);

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/nullable-chaining-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/nullable-chaining.yaml'));
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
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('exists')->andReturn(false);

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/foreign-key-on-delete.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/foreign-key-on-delete.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_creates_foreign_keys_with_on_delete_laravel6()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->files->expects('exists')->andReturn(false);

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/foreign-key-on-delete-laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/foreign-key-on-delete.yaml'));
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
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $post_migration = str_replace('timestamp', $now->copy()->subSeconds(2)->format('Y_m_d_His'), 'database/migrations/timestamp_create_posts_table.php');
        $user_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $image_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_images_table.php');

        $this->files->expects('exists')->times(3)->andReturn(false);

        $this->files->expects('put')
            ->with($post_migration, $this->fixture('migrations/polymorphic_relationships_posts_table.php'));
        $this->files->expects('put')
            ->with($user_migration, $this->fixture('migrations/polymorphic_relationships_users_table.php'));
        $this->files->expects('put')
            ->with($image_migration, $this->fixture('migrations/polymorphic_relationships_images_table.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/polymorphic-relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$post_migration, $user_migration, $image_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     * @environment-setup useLaravel6
     */
    public function output_works_with_polymorphic_relationships_laravel6()
    {
        $this->files->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $post_migration = str_replace('timestamp', $now->copy()->subSeconds(2)->format('Y_m_d_His'), 'database/migrations/timestamp_create_posts_table.php');
        $user_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $image_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_images_table.php');

        $this->files->expects('exists')->times(3)->andReturn(false);

        $this->files->expects('put')
            ->with($post_migration, $this->fixture('migrations/polymorphic_relationships_posts_table_laravel6.php'));
        $this->files->expects('put')
            ->with($user_migration, $this->fixture('migrations/polymorphic_relationships_users_table_laravel6.php'));
        $this->files->expects('put')
            ->with($image_migration, $this->fixture('migrations/polymorphic_relationships_images_table_laravel6.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/polymorphic-relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$post_migration, $user_migration, $image_migration]], $this->subject->output($tree));
    }

    public function modelTreeDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'database/migrations/timestamp_create_posts_table.php', 'migrations/readme-example.php'],
            ['drafts/model-identities.yaml', 'database/migrations/timestamp_create_relationships_table.php', 'migrations/identity-columns.php'],
            ['drafts/model-modifiers.yaml', 'database/migrations/timestamp_create_modifiers_table.php', 'migrations/model-modifiers.php'],
            ['drafts/soft-deletes.yaml', 'database/migrations/timestamp_create_comments_table.php', 'migrations/soft-deletes.php'],
            ['drafts/with-timezones.yaml', 'database/migrations/timestamp_create_comments_table.php', 'migrations/with-timezones.php'],
            ['drafts/relationships.yaml', 'database/migrations/timestamp_create_comments_table.php', 'migrations/relationships.php'],
            ['drafts/indexes.yaml', 'database/migrations/timestamp_create_posts_table.php', 'migrations/indexes.php'],
            ['drafts/unconventional.yaml', 'database/migrations/timestamp_create_teams_table.php', 'migrations/unconventional.php'],
            ['drafts/optimize.yaml', 'database/migrations/timestamp_create_optimizes_table.php', 'migrations/optimize.php'],
            ['drafts/model-key-constraints.yaml', 'database/migrations/timestamp_create_orders_table.php', 'migrations/model-key-constraints.php'],
            ['drafts/disable-auto-columns.yaml', 'database/migrations/timestamp_create_states_table.php', 'migrations/disable-auto-columns.php'],
            ['drafts/uuid-shorthand.yaml', 'database/migrations/timestamp_create_people_table.php', 'migrations/uuid-shorthand.php'],
            ['drafts/unconventional-foreign-key.yaml', 'database/migrations/timestamp_create_states_table.php', 'migrations/unconventional-foreign-key.php'],
            ['drafts/resource-statements.yaml', 'database/migrations/timestamp_create_users_table.php', 'migrations/resource-statements.php'],
            ['drafts/enum-options.yaml', 'database/migrations/timestamp_create_messages_table.php', 'migrations/enum-options.php'],
            ['drafts/columns-with-comments.yaml', 'database/migrations/timestamp_create_professions_table.php', 'migrations/columns-with-comments.php'],
        ];
    }
}
