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

    private $files;

    /** @var TestGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new TestGenerator($this->files);

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
        $this->files->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     * @dataProvider controllerTreeDataProvider
     */
    public function output_generates_test_for_controller_tree($definition, $path, $test)
    {
        $this->files->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->files->expects('stub')
            ->with('test.case.stub')
            ->andReturn($this->stub('test.case.stub'));
        $dirname = dirname($path);
        $this->files->expects('exists')
            ->with($dirname)
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with($dirname, 0755, true);
        $this->files->expects('put')
            ->with($path, $this->fixture($test));

        $tokens = $this->blueprint->parse($this->fixture($definition));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => [$path]], $this->subject->output($tree));
    }

    /**
    * @test
    */
    public function output_works_for_pascal_case_definition()
    {
        $this->files->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->files->expects('stub')
            ->with('test.case.stub')
            ->andReturn($this->stub('test.case.stub'));

        $certificateControllerTest = 'tests/Feature/Http/Controllers/CertificateControllerTest.php';
        $certificateTypeControllerTest = 'tests/Feature/Http/Controllers/CertificateTypeControllerTest.php';

        $this->files->expects('exists')
            ->with(dirname($certificateControllerTest))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($certificateControllerTest, $this->fixture('tests/certificate-pascal-case-example.php'));

        $this->files->expects('exists')
            ->with(dirname($certificateTypeControllerTest))
            ->andReturnTrue();
        $this->files->expects('put')
            ->with($certificateTypeControllerTest, $this->fixture('tests/certificate-type-pascal-case-example.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/pascal-case.yaml'));
        $tree = $this->blueprint->analyze($tokens);
        $this->assertEquals(['created' => [$certificateControllerTest, $certificateTypeControllerTest]], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_test_for_controller_tree_using_cached_model()
    {
        $this->files->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->files->expects('stub')
            ->with('test.case.stub')
            ->andReturn($this->stub('test.case.stub'));
        $this->files->expects('exists')
            ->with('tests/Feature/Http/Controllers')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('tests/Feature/Http/Controllers', 0755, true);
        $this->files->expects('put')
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
    public function output_generates_tests_with_models_with_custom_namespace_correctly()
    {
        $definition = 'drafts/models-with-custom-namespace.yaml';
        $path = 'tests/Feature/Http/Controllers/CategoryControllerTest.php';
        $test = 'tests/models-with-custom-namespace.php';

        $this->app['config']->set('blueprint.models_namespace', 'Models');

        $this->files->expects('stub')
            ->with('test.class.stub')
            ->andReturn($this->stub('test.class.stub'));

        $this->files->expects('stub')
            ->with('test.case.stub')
            ->andReturn($this->stub('test.case.stub'));
        $dirname = dirname($path);
        $this->files->expects('exists')
            ->with($dirname)
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
        ->with($dirname, 0755, true);
        $this->files->expects('put')
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
        ];
    }
}
