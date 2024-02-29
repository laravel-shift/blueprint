<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\PhpUnitTestGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see PhpUnitTestGenerator
 */
final class PhpUnitTestGeneratorTest extends TestCase
{
    private $blueprint;

    /** @var PhpUnitTestGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new PhpUnitTestGenerator($this->filesystem);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    #[Test]
    public function output_writes_nothing_for_empty_tree(): void
    {
        $this->filesystem->expects('stub')
            ->with('phpunit.test.class.stub')
            ->andReturn($this->stub('phpunit.test.class.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    #[Test]
    #[DataProvider('controllerTreeDataProvider')]
    public function output_generates_test_for_controller_tree($definition, $path, $test): void
    {
        $this->filesystem->expects('stub')
            ->with('phpunit.test.class.stub')
            ->andReturn($this->stub('phpunit.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('phpunit.test.case.stub')
            ->andReturn($this->stub('phpunit.test.case.stub'));

        $paths = collect($path)->combine($test)->toArray();
        foreach ($paths as $path => $test) {
            $dirname = dirname($path);

            $this->filesystem->expects('exists')
                ->with($dirname)
                ->andReturnFalse();

            $this->filesystem->expects('makeDirectory')
                ->with($dirname, 0755, true);

            $this->filesystem->expects('put')
                ->with($path, $this->fixture($test));
        }

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => array_keys($paths)], $this->subject->output($tree));
    }

    #[Test]
    public function output_works_for_pascal_case_definition(): void
    {
        $this->filesystem->expects('stub')
            ->with('phpunit.test.class.stub')
            ->andReturn($this->stub('phpunit.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('phpunit.test.case.stub')
            ->andReturn($this->stub('phpunit.test.case.stub'));

        $certificateControllerTest = 'tests/Feature/Http/Controllers/CertificateControllerTest.php';
        $certificateTypeControllerTest = 'tests/Feature/Http/Controllers/CertificateTypeControllerTest.php';

        $this->filesystem->expects('exists')
            ->with(dirname($certificateControllerTest))
            ->andReturnTrue();

        $this->filesystem->expects('put')
            ->with($certificateControllerTest, $this->fixture('tests/phpunit/certificate-pascal-case-example.php'));

        $this->filesystem->expects('exists')
            ->with(dirname($certificateTypeControllerTest))
            ->andReturnTrue();

        $this->filesystem->expects('put')
            ->with($certificateTypeControllerTest, $this->fixture('tests/phpunit/certificate-type-pascal-case-example.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/pascal-case.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$certificateControllerTest, $certificateTypeControllerTest]], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_test_for_controller_tree_using_cached_model(): void
    {
        $this->filesystem->expects('stub')
            ->with('phpunit.test.class.stub')
            ->andReturn($this->stub('phpunit.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('phpunit.test.case.stub')
            ->andReturn($this->stub('phpunit.test.case.stub'));

        $this->filesystem->expects('exists')
            ->with('tests/Feature/Http/Controllers')
            ->andReturnFalse();

        $this->filesystem->expects('makeDirectory')
            ->with('tests/Feature/Http/Controllers', 0755, true);

        $this->filesystem->expects('put')
            ->with('tests/Feature/Http/Controllers/UserControllerTest.php', $this->fixture('tests/phpunit/reference-cache.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/reference-cache.yaml'));
        $tokens['cache'] = [
            'User' => [
                'email' => 'string',
                'password' => 'string',
            ],
        ];
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['tests/Feature/Http/Controllers/UserControllerTest.php']], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_tests_with_models_with_custom_namespace_correctly(): void
    {
        $definition = 'drafts/models-with-custom-namespace.yaml';
        $path = 'tests/Feature/Http/Controllers/CategoryControllerTest.php';
        $test = 'tests/phpunit/models-with-custom-namespace.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->filesystem->expects('stub')
            ->with('phpunit.test.class.stub')
            ->andReturn($this->stub('phpunit.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('phpunit.test.case.stub')
            ->andReturn($this->stub('phpunit.test.case.stub'));

        $dirname = dirname($path);
        $this->filesystem->expects('exists')
            ->with($dirname)
            ->andReturnFalse();

        $this->filesystem->expects('makeDirectory')
            ->with($dirname, 0755, true);

        $this->filesystem->expects('put')
            ->with($path, $this->fixture($test));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_tests_with_singular_route_names(): void
    {
        $definition = 'drafts/models-with-custom-namespace.yaml';
        $path = 'tests/Feature/Http/Controllers/CategoryControllerTest.php';
        $test = 'tests/phpunit/routes-with-singular-names.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');
        $this->app['config']->set('blueprint.singular_routes', true);

        $this->filesystem->expects('stub')
            ->with('phpunit.test.class.stub')
            ->andReturn($this->stub('phpunit.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('phpunit.test.case.stub')
            ->andReturn($this->stub('phpunit.test.case.stub'));

        $dirname = dirname($path);
        $this->filesystem->expects('exists')
            ->with($dirname)
            ->andReturnFalse();

        $this->filesystem->expects('makeDirectory')
            ->with($dirname, 0755, true);

        $this->filesystem->expects('put')
            ->with($path, $this->fixture($test));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    public static function controllerTreeDataProvider(): array
    {
        return [
            ['drafts/readme-example.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/phpunit/readme-example.php'],
            ['drafts/readme-example-notification-facade.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/phpunit/readme-example-notification.php'],
            ['drafts/readme-example-notification-model.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/phpunit/readme-example-notification.php'],
            ['drafts/respond-statements.yaml', 'tests/Feature/Http/Controllers/Api/PostControllerTest.php', 'tests/phpunit/respond-statements.php'],
            ['drafts/full-crud-example.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/phpunit/full-crud-example.php'],
            ['drafts/crud-show-only.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/phpunit/crud-show-only.php'],
            ['drafts/model-reference-validate.yaml', 'tests/Feature/Http/Controllers/CertificateControllerTest.php', 'tests/phpunit/api-shorthand-validation.php'],
            ['drafts/controllers-only-no-context.yaml', 'tests/Feature/Http/Controllers/ReportControllerTest.php', 'tests/phpunit/controllers-only-no-context.php'],
            ['drafts/date-formats.yaml', 'tests/Feature/Http/Controllers/DateControllerTest.php', 'tests/phpunit/date-formats.php'],
            ['drafts/test-relationships.yaml', 'tests/Feature/Http/Controllers/ConferenceControllerTest.php', 'tests/phpunit/test-relationships.php'],
            ['drafts/call-to-a-member-function-columns-on-null.yaml', [
                'tests/Feature/Http/Controllers/SubscriptionControllerTest.php',
                'tests/Feature/Http/Controllers/TelegramControllerTest.php',
                'tests/Feature/Http/Controllers/PaymentControllerTest.php',
                'tests/Feature/Http/Controllers/Api/PaymentControllerTest.php',
            ], [
                'tests/phpunit/call-to-a-member-function-columns-on-null-SubscriptionControllerTest.php',
                'tests/phpunit/call-to-a-member-function-columns-on-null-TelegramControllerTest.php',
                'tests/phpunit/call-to-a-member-function-columns-on-null-PaymentControllerTest.php',
                'tests/phpunit/call-to-a-member-function-columns-on-null-Api-PaymentControllerTest.php',
            ]],
        ];
    }
}
