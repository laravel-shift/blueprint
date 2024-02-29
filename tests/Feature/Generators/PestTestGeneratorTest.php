<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\PestTestGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see PestTestGenerator
 */
final class PestTestGeneratorTest extends TestCase
{
    private $blueprint;

    /** @var PestTestGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new PestTestGenerator($this->filesystem);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    #[Test]
    public function output_writes_nothing_for_empty_tree(): void
    {
        $this->filesystem->expects('stub')
            ->with('pest.test.class.stub')
            ->andReturn($this->stub('pest.test.class.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    #[Test]
    #[DataProvider('controllerTreeDataProvider')]
    public function output_generates_test_for_controller_tree($definition, $path, $test): void
    {
        $this->filesystem->expects('stub')
            ->with('pest.test.class.stub')
            ->andReturn($this->stub('pest.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('pest.test.case.stub')
            ->andReturn($this->stub('pest.test.case.stub'));

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

        $this->subject->output($tree);
    }

    #[Test]
    public function output_works_for_pascal_case_definition(): void
    {
        $this->filesystem->expects('stub')
            ->with('pest.test.class.stub')
            ->andReturn($this->stub('pest.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('pest.test.case.stub')
            ->andReturn($this->stub('pest.test.case.stub'));

        $certificateControllerTest = 'tests/Feature/Http/Controllers/CertificateControllerTest.php';
        $certificateTypeControllerTest = 'tests/Feature/Http/Controllers/CertificateTypeControllerTest.php';

        $this->filesystem->expects('exists')
            ->with(dirname($certificateControllerTest))
            ->andReturnTrue();

        $this->filesystem->expects('put')
            ->with($certificateControllerTest, $this->fixture('tests/pest/certificate-pascal-case-example.php'));

        $this->filesystem->expects('exists')
            ->with(dirname($certificateTypeControllerTest))
            ->andReturnTrue();

        $this->filesystem->expects('put')
            ->with($certificateTypeControllerTest, $this->fixture('tests/pest/certificate-type-pascal-case-example.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/pascal-case.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$certificateControllerTest, $certificateTypeControllerTest]], $this->subject->output($tree));
    }

    #[Test]
    public function output_generates_test_for_controller_tree_using_cached_model(): void
    {
        $this->filesystem->expects('stub')
            ->with('pest.test.class.stub')
            ->andReturn($this->stub('pest.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('pest.test.case.stub')
            ->andReturn($this->stub('pest.test.case.stub'));

        $this->filesystem->expects('exists')
            ->with('tests/Feature/Http/Controllers')
            ->andReturnFalse();

        $this->filesystem->expects('makeDirectory')
            ->with('tests/Feature/Http/Controllers', 0755, true);

        $this->filesystem->expects('put')
            ->with('tests/Feature/Http/Controllers/UserControllerTest.php', $this->fixture('tests/pest/reference-cache.php'));

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
        $test = 'tests/pest/models-with-custom-namespace.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->filesystem->expects('stub')
            ->with('pest.test.class.stub')
            ->andReturn($this->stub('pest.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('pest.test.case.stub')
            ->andReturn($this->stub('pest.test.case.stub'));

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
        $test = 'tests/pest/routes-with-singular-names.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');
        $this->app['config']->set('blueprint.singular_routes', true);

        $this->filesystem->expects('stub')
            ->with('pest.test.class.stub')
            ->andReturn($this->stub('pest.test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('pest.test.case.stub')
            ->andReturn($this->stub('pest.test.case.stub'));

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
            ['drafts/readme-example.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/pest/readme-example.php'],
            ['drafts/readme-example-notification-facade.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/pest/readme-example-notification.php'],
            ['drafts/readme-example-notification-model.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/pest/readme-example-notification.php'],
            ['drafts/respond-statements.yaml', 'tests/Feature/Http/Controllers/Api/PostControllerTest.php', 'tests/pest/respond-statements.php'],
            ['drafts/full-crud-example.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/pest/full-crud-example.php'],
            ['drafts/crud-show-only.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/pest/crud-show-only.php'],
            ['drafts/model-reference-validate.yaml', 'tests/Feature/Http/Controllers/CertificateControllerTest.php', 'tests/pest/api-shorthand-validation.php'],
            ['drafts/controllers-only-no-context.yaml', 'tests/Feature/Http/Controllers/ReportControllerTest.php', 'tests/pest/controllers-only-no-context.php'],
            ['drafts/date-formats.yaml', 'tests/Feature/Http/Controllers/DateControllerTest.php', 'tests/pest/date-formats.php'],
            ['drafts/call-to-a-member-function-columns-on-null.yaml', [
                'tests/Feature/Http/Controllers/SubscriptionControllerTest.php',
                'tests/Feature/Http/Controllers/TelegramControllerTest.php',
                'tests/Feature/Http/Controllers/PaymentControllerTest.php',
                'tests/Feature/Http/Controllers/Api/PaymentControllerTest.php',
            ], [
                'tests/pest/call-to-a-member-function-columns-on-null-SubscriptionControllerTest.php',
                'tests/pest/call-to-a-member-function-columns-on-null-TelegramControllerTest.php',
                'tests/pest/call-to-a-member-function-columns-on-null-PaymentControllerTest.php',
                'tests/pest/call-to-a-member-function-columns-on-null-Api-PaymentControllerTest.php',
            ]],
        ];
    }
}
