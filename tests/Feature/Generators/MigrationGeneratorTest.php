<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\MigrationGenerator;
use Blueprint\Tree;
use Carbon\Carbon;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

/**
 * @see MigrationGenerator
 */
class MigrationGeneratorTest extends TestCase
{
    private $blueprint;

    protected $files;

    /** @var MigrationGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['models' => []])));
    }

    /**
     * @test
     */
    public function output_writes_migration_for_foreign_shorthand()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->filesystem->expects('exists')->andReturn(false);

        $this->filesystem->expects('put')
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
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $post_path = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_posts_table.php');
        $comment_path = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->filesystem->expects('exists')->twice()->andReturn(false);

        $this->filesystem->expects('put')
            ->with($post_path, $this->fixture('migrations/posts.php'));
        $this->filesystem->expects('put')
            ->with($comment_path, $this->fixture('migrations/comments.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/multiple-models.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$post_path, $comment_path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_proper_pascal_case_model_names()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $broker_path = str_replace('timestamp', $now->copy()->subSeconds(2)->format('Y_m_d_His'), 'database/migrations/timestamp_create_brokers_table.php');
        $broker_type_path = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_broker_types_table.php');
        $broker_broker_type_path = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_broker_broker_type_table.php');

        $this->filesystem->expects('exists')->times(3)->andReturn(false);

        $this->filesystem->expects('put')
            ->with($broker_path, $this->fixture('migrations/pascal-case-model-names-broker.php'));
        $this->filesystem->expects('put')
            ->with($broker_type_path, $this->fixture('migrations/pascal-case-model-names-broker-type.php'));
        $this->filesystem->expects('put')
            ->with($broker_broker_type_path, $this->fixture('migrations/pascal-case-model-names-broker-broker-type.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/pascal-case-model-names.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$broker_path, $broker_type_path, $broker_broker_type_path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_constraints_for_unconventional_foreign_reference_migration()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->filesystem->expects('exists')->andReturn(false);

        $this->filesystem->expects('put')
            ->with($model_migration, $this->fixture('migrations/relationships-constraints.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_creates_pivot_table_migration()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->filesystem->expects('exists')->twice()->andReturn(false);

        $this->filesystem->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many.php'));
        $this->filesystem->expects('put')
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
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $yday = Carbon::yesterday();

        $model_migration = str_replace('timestamp', $yday->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $yday->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->filesystem->expects('files')
            ->with('database/migrations/')
            ->twice()
            ->andReturn([
                new SplFileInfo($model_migration, '', ''),
                new SplFileInfo($pivot_migration, '', ''),
            ]);

        $this->filesystem->expects('exists')->with($model_migration)->andReturn(true);
        $this->filesystem->expects('exists')->with($pivot_migration)->andReturn(true);

        $this->filesystem->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many.php'));
        $this->filesystem->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['updated' => [$model_migration, $pivot_migration]], $this->subject->output($tree, true));
    }

    /**
     * @test
     */
    public function output_also_creates_constraints_for_pivot_table_migration()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_journeys_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_diary_journey_table.php');

        $this->filesystem->expects('exists')->twice()->andReturn(false);

        $this->filesystem->expects('put')
            ->with($model_migration, $this->fixture('migrations/belongs-to-many-key-constraints.php'));

        $this->filesystem->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-pivot-key-constraints.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_does_not_duplicate_pivot_table_migration()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $company_migration = str_replace('timestamp', $now->copy()->subSeconds(2)->format('Y_m_d_His'), 'database/migrations/timestamp_create_companies_table.php');
        $people_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_people_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_company_person_table.php');

        $this->filesystem->expects('exists')->times(3)->andReturn(false);

        $this->filesystem->expects('put')
            ->with($company_migration, $this->fixture('migrations/belongs-to-many-duplicated-company.php'));
        $this->filesystem->expects('put')
            ->with($people_migration, $this->fixture('migrations/belongs-to-many-duplicated-people.php'));
        $this->filesystem->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/belongs-to-many-duplicated-pivot.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/belongs-to-many-duplicated-pivot.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$company_migration, $people_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_creates_pivot_table_migration_with_custom_name()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_test_table.php');

        $this->filesystem->expects('exists')->twice()->andReturn(false);

        $this->filesystem->expects('put')
            ->with($model_migration, $this->fixture('migrations/custom-pivot-table-name-user.php'));
        $this->filesystem->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/custom-pivot-table-name-test.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/custom-pivot-table-name.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_pivot_table_migration_correctly_when_model_name_contains_path_prefix()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_regions_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_city_region_table.php');

        $this->filesystem->expects('exists')->twice()->andReturn(false);

        $this->filesystem->expects('put')
            ->with($model_migration, $this->fixture('migrations/with-path-prefix-table-name-region.php'));
        $this->filesystem->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/with-path-prefix-table-name-city-region.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/with-path-prefix.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $pivot_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_also_creates_many_to_many_polymorphic_intermediate_table_migration()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_tags_table.php');
        $poly_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_tagables_table.php');

        $this->filesystem->expects('exists')->twice()->andReturn(false);

        $this->filesystem->expects('put')
            ->with($model_migration, $this->fixture('migrations/morphed-by-many.php'));
        $this->filesystem->expects('put')
            ->with($poly_migration, $this->fixture('migrations/morphed-by-many-intermediate.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/morphed-by-many.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration, $poly_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_foreign_keys_with_nullable_chained_correctly()
    {
        $this->app->config->set('blueprint.use_constraints', true);
        $this->app->config->set('blueprint.on_delete', 'null');

        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_carts_table.php');

        $this->filesystem->expects('exists')->andReturn(false);

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/nullable-chaining.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/nullable-chaining.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_nullable_foreign_key_without_column_type_beeing_id()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->filesystem->expects('exists')->with($model_migration)->andReturn(false);

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/nullable-columns-with-foreign.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/nullable-columns-with-foreign.yaml'));

        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_creates_foreign_keys_with_on_delete()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $model_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_comments_table.php');

        $this->filesystem->expects('exists')->andReturn(false);

        $this->files
            ->expects('put')
            ->with($model_migration, $this->fixture('migrations/foreign-key-on-delete.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/foreign-key-on-delete.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$model_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_works_with_polymorphic_relationships()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $post_migration = str_replace('timestamp', $now->copy()->subSeconds(2)->format('Y_m_d_His'), 'database/migrations/timestamp_create_posts_table.php');
        $user_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $image_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_images_table.php');

        $this->filesystem->expects('exists')->times(3)->andReturn(false);

        $this->filesystem->expects('put')
            ->with($post_migration, $this->fixture('migrations/polymorphic_relationships_posts_table.php'));
        $this->filesystem->expects('put')
            ->with($user_migration, $this->fixture('migrations/polymorphic_relationships_users_table.php'));
        $this->filesystem->expects('put')
            ->with($image_migration, $this->fixture('migrations/polymorphic_relationships_images_table.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/polymorphic-relationships.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$post_migration, $user_migration, $image_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_works_with_multiple_morphto_statements_in_polymorphic_relationship()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $image_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_images_table.php');

        $this->filesystem->expects('exists')->andReturn(false);

        $this->filesystem->expects('put')
            ->with($image_migration, $this->fixture('migrations/polymorphic_relationships_images_table_multiple_morphto.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/polymorphic-relationships-multiple-morphto.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$image_migration]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_does_not_generate_relationship_for_uuid()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = 'database/migrations/' . $now->format('Y_m_d_His') . '_create_vats_table.php';

        $this->filesystem->expects('exists')
            ->with($timestamp_path)
            ->andReturn(false);

        $this->filesystem->expects('put')
            ->with($timestamp_path, $this->fixture('migrations/uuid-without-relationship.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/uuid-without-relationship.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$timestamp_path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_constraint_for_uuid()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = 'database/migrations/' . $now->format('Y_m_d_His') . '_create_people_table.php';

        $this->filesystem->expects('exists')
            ->with($timestamp_path)
            ->andReturn(false);

        $this->filesystem->expects('put')
            ->with($timestamp_path, $this->fixture('migrations/uuid-shorthand-constraint.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/uuid-shorthand.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$timestamp_path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_softdelete_column_last()
    {
        $this->app->config->set('blueprint.use_constraints', true);

        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $timestamp_path = 'database/migrations/' . $now->format('Y_m_d_His') . '_create_comments_table.php';

        $this->filesystem->expects('exists')
            ->with($timestamp_path)
            ->andReturn(false);

        $this->filesystem->expects('put')
            ->with($timestamp_path, $this->fixture('migrations/soft-deletes-respect-order.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/soft-deletes-respect-order.yaml'));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$timestamp_path]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_custom_pivot_tables()
    {
        $this->filesystem->expects('stub')
            ->with('migration.stub')
            ->andReturn($this->stub('migration.stub'));

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $user_migration = str_replace('timestamp', $now->copy()->subSeconds(2)->format('Y_m_d_His'), 'database/migrations/timestamp_create_users_table.php');
        $team_migration = str_replace('timestamp', $now->copy()->subSecond()->format('Y_m_d_His'), 'database/migrations/timestamp_create_teams_table.php');
        $pivot_migration = str_replace('timestamp', $now->format('Y_m_d_His'), 'database/migrations/timestamp_create_team_user_table.php');

        $this->filesystem->expects('exists')->times(3)->andReturn(false);

        $this->filesystem->expects('put')
            ->with($user_migration, $this->fixture('migrations/custom_pivot_users_table.php'));
        $this->filesystem->expects('put')
            ->with($team_migration, $this->fixture('migrations/custom_pivot_teams_table.php'));
        $this->filesystem->expects('put')
            ->with($pivot_migration, $this->fixture('migrations/custom_pivot_team_user_table.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/custom-pivot.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$user_migration, $team_migration, $pivot_migration]], $this->subject->output($tree));
    }

    public function modelTreeDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'database/migrations/timestamp_create_posts_table.php', 'migrations/readme-example.php'],
            ['drafts/model-identities.yaml', 'database/migrations/timestamp_create_relationships_table.php', 'migrations/identity-columns.php'],
            ['drafts/model-modifiers.yaml', 'database/migrations/timestamp_create_modifiers_table.php', 'migrations/model-modifiers.php'],
            ['drafts/model-numeric-defaults.yaml', 'database/migrations/timestamp_create_numerics_table.php', 'migrations/model-numeric-defaults.php'],
            ['drafts/soft-deletes.yaml', 'database/migrations/timestamp_create_comments_table.php', 'migrations/soft-deletes.php'],
            ['drafts/with-timezones.yaml', 'database/migrations/timestamp_create_comments_table.php', 'migrations/with-timezones.php'],
            ['drafts/relationships.yaml', 'database/migrations/timestamp_create_comments_table.php', 'migrations/relationships.php'],
            ['drafts/indexes.yaml', 'database/migrations/timestamp_create_posts_table.php', 'migrations/indexes.php'],
            ['drafts/custom-indexes.yaml', 'database/migrations/timestamp_create_cooltables_table.php', 'migrations/custom-indexes.php'],
            ['drafts/unconventional.yaml', 'database/migrations/timestamp_create_teams_table.php', 'migrations/unconventional.php'],
            ['drafts/optimize.yaml', 'database/migrations/timestamp_create_optimizes_table.php', 'migrations/optimize.php'],
            ['drafts/model-key-constraints.yaml', 'database/migrations/timestamp_create_orders_table.php', 'migrations/model-key-constraints.php'],
            ['drafts/disable-auto-columns.yaml', 'database/migrations/timestamp_create_states_table.php', 'migrations/disable-auto-columns.php'],
            ['drafts/uuid-shorthand.yaml', 'database/migrations/timestamp_create_people_table.php', 'migrations/uuid-shorthand.php'],
            ['drafts/uuid-shorthand-invalid-relationship.yaml', 'database/migrations/timestamp_create_age_cohorts_table.php', 'migrations/uuid-shorthand-invalid-relationship.php'],
            ['drafts/uuid-without-relationship.yaml', 'database/migrations/timestamp_create_vats_table.php', 'migrations/uuid-without-relationship.php'],
            ['drafts/unconventional-foreign-key.yaml', 'database/migrations/timestamp_create_states_table.php', 'migrations/unconventional-foreign-key.php'],
            ['drafts/resource-statements.yaml', 'database/migrations/timestamp_create_users_table.php', 'migrations/resource-statements.php'],
            ['drafts/enum-options.yaml', 'database/migrations/timestamp_create_messages_table.php', 'migrations/enum-options.php'],
            ['drafts/columns-with-comments.yaml', 'database/migrations/timestamp_create_professions_table.php', 'migrations/columns-with-comments.php'],
            ['drafts/boolean-column-default.yaml', 'database/migrations/timestamp_create_posts_table.php', 'migrations/boolean-column-default.php'],
            ['drafts/foreign-with-class.yaml', 'database/migrations/timestamp_create_events_table.php', 'migrations/foreign-with-class.php'],
            ['drafts/full-text.yaml', 'database/migrations/timestamp_create_posts_table.php', 'migrations/full-text.php'],
            ['drafts/model-with-meta.yaml', 'database/migrations/timestamp_create_post_table.php', 'migrations/model-with-meta.php'],
        ];
    }
}
