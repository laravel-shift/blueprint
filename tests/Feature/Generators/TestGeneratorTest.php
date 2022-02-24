<?php

namespace Tests\Feature\Generators;

use Blueprint\Blueprint;
use Blueprint\Generators\TestGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use Tests\TestCase;

/**
 * @see TestGenerator
 */
class TestGeneratorTest extends TestCase
{
    private $blueprint;

    /** @var TestGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new TestGenerator($this->filesystem);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ModelLexer());
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->filesystem->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     * @dataProvider controllerTreeDataProvider
     */
    public function output_generates_test_for_controller_tree_l8($definition, $path, $test)
    {
        $this->filesystem->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('test.case.stub')
            ->andReturn($this->stub('test.case.stub'));

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

    /**
    * @test
    */
    public function output_works_for_pascal_case_definition_l8()
    {
        $this->filesystem->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('test.case.stub')
            ->andReturn($this->stub('test.case.stub'));

        $certificateControllerTest = 'tests/Feature/Http/Controllers/CertificateControllerTest.php';
        $certificateTypeControllerTest = 'tests/Feature/Http/Controllers/CertificateTypeControllerTest.php';

        $this->filesystem->expects('exists')
            ->with(dirname($certificateControllerTest))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($certificateControllerTest, $this->fixture('tests/certificate-pascal-case-example.php'));

        $this->filesystem->expects('exists')
            ->with(dirname($certificateTypeControllerTest))
            ->andReturnTrue();
        $this->filesystem->expects('put')
            ->with($certificateTypeControllerTest, $this->fixture('tests/certificate-type-pascal-case-example.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/pascal-case.yaml'));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$certificateControllerTest, $certificateTypeControllerTest]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_test_for_controller_tree_using_cached_model_l8()
    {
        $this->filesystem->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('test.case.stub')
            ->andReturn($this->stub('test.case.stub'));
        $this->filesystem->expects('exists')
            ->with('tests/Feature/Http/Controllers')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('tests/Feature/Http/Controllers', 0755, true);
        $this->filesystem->expects('put')
            ->with('tests/Feature/Http/Controllers/UserControllerTest.php', $this->fixture('tests/reference-cache.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/reference-cache.yaml'));
        $tokens['cache'] = [
            'User' => [
                'email' => 'string',
                'password' => 'string',
            ]
        ];
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['tests/Feature/Http/Controllers/UserControllerTest.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_tests_with_models_with_custom_namespace_correctly_l8()
    {
        $definition = 'drafts/models-with-custom-namespace.yaml';
        $path = 'tests/Feature/Http/Controllers/CategoryControllerTest.php';
        $test = 'tests/models-with-custom-namespace.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->filesystem->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('test.case.stub')
            ->andReturn($this->stub('test.case.stub'));
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

    /**
     * @test
     */
    public function output_using_return_types()
    {
        $definition = 'drafts/readme-example.yaml';
        $path = 'tests/Feature/Http/Controllers/PostControllerTest.php';
        $test = 'tests/return-type-declarations.php';

        $this->app['config']->set('blueprint.use_return_types', true);

        $this->filesystem->expects('stub')
        ->with('test.class.stub')
        ->andReturn($this->stub('test.class.stub'));

        $this->filesystem->expects('stub')
            ->with('test.case.stub')
            ->andReturn($this->stub('test.case.stub'));

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

    public function controllerTreeDataProvider()
    {
        return [
            ['drafts/readme-example.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/readme-example.php'],
            ['drafts/readme-example-notification-facade.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/readme-example-notification.php'],
            ['drafts/readme-example-notification-model.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/readme-example-notification.php'],
            ['drafts/respond-statements.yaml', 'tests/Feature/Http/Controllers/Api/PostControllerTest.php', 'tests/respond-statements.php'],
            ['drafts/full-crud-example.yaml', 'tests/Feature/Http/Controllers/PostControllerTest.php', 'tests/full-crud-example.php'],
            ['drafts/model-reference-validate.yaml', 'tests/Feature/Http/Controllers/CertificateControllerTest.php', 'tests/api-shorthand-validation.php'],
            ['drafts/controllers-only-no-context.yaml', 'tests/Feature/Http/Controllers/ReportControllerTest.php', 'tests/controllers-only-no-context.php'],
            ['drafts/call-to-a-member-function-columns-on-null.yaml', [
                'tests/Feature/Http/Controllers/SubscriptionControllerTest.php',
                'tests/Feature/Http/Controllers/TelegramControllerTest.php',
                'tests/Feature/Http/Controllers/PaymentControllerTest.php',
                'tests/Feature/Http/Controllers/Api/PaymentControllerTest.php'
            ], [
                'tests/call-to-a-member-function-columns-on-null-SubscriptionControllerTest.php',
                'tests/call-to-a-member-function-columns-on-null-TelegramControllerTest.php',
                'tests/call-to-a-member-function-columns-on-null-PaymentControllerTest.php',
                'tests/call-to-a-member-function-columns-on-null-Api-PaymentControllerTest.php',
            ]],
        ];
    }
}
