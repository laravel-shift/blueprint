<?php

namespace Tests\Feature\Generator\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\FormRequestGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use Tests\TestCase;

/**
 * @see FormRequestGenerator
 */
class FormRequestGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var FormRequestGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new FormRequestGenerator($this->files);

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
            ->with('request.stub')
            ->andReturn($this->stub('request.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     */
    public function output_writes_nothing_without_validate_statements()
    {
        $this->files->expects('stub')
            ->with('request.stub')
            ->andReturn($this->stub('request.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/controllers-only.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_form_requests()
    {
        $this->files->expects('stub')
            ->with('request.stub')
            ->andReturn($this->stub('request.stub'));

        $this->files->shouldReceive('exists')
            ->times(3)
            ->with('app/Http/Requests')
            ->andReturns(false, true, true);
        $this->files->expects('exists')
            ->with('app/Http/Requests/PostIndexRequest.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('app/Http/Requests', 0755, true);
        $this->files->expects('put')
            ->with('app/Http/Requests/PostIndexRequest.php', $this->fixture('form-requests/post-index.php'));

        $this->files->expects('exists')
            ->with('app/Http/Requests/PostStoreRequest.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Http/Requests/PostStoreRequest.php', $this->fixture('form-requests/post-store.php'));

        $this->files->expects('exists')
            ->with('app/Http/Requests/OtherStoreRequest.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Http/Requests/OtherStoreRequest.php', $this->fixture('form-requests/other-store.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/validate-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Http/Requests/PostIndexRequest.php', 'app/Http/Requests/PostStoreRequest.php', 'app/Http/Requests/OtherStoreRequest.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_form_requests_with_support_for_model_reference_in_validate_statement()
    {
        $this->files->expects('stub')
            ->with('request.stub')
            ->andReturn($this->stub('request.stub'));

        $this->files->shouldReceive('exists')
            ->twice()
            ->with('app/Http/Requests')
            ->andReturns(false, false);

        $this->files->expects('makeDirectory')
            ->twice()
            ->with('app/Http/Requests', 0755, true)
            ->andReturns(true, false);

        $this->files->expects('exists')
            ->with('app/Http/Requests/CertificateStoreRequest.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Http/Requests/CertificateStoreRequest.php', $this->fixture('form-requests/certificate-store.php'));

        $this->files->expects('exists')
            ->with('app/Http/Requests/CertificateUpdateRequest.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Http/Requests/CertificateUpdateRequest.php', $this->fixture('form-requests/certificate-update.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/model-reference-validate.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Http/Requests/CertificateStoreRequest.php', 'app/Http/Requests/CertificateUpdateRequest.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_form_requests()
    {
        $this->files->expects('stub')
            ->with('request.stub')
            ->andReturn($this->stub('request.stub'));

        $this->files->expects('exists')
            ->with('app/Http/Requests/PostIndexRequest.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('app/Http/Requests/PostStoreRequest.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('app/Http/Requests/OtherStoreRequest.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/validate-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_supports_nested_form_requests()
    {
        $this->files->expects('stub')
            ->with('request.stub')
            ->andReturn($this->stub('request.stub'));

        $this->files->expects('exists')
            ->with('app/Http/Requests/Admin')
            ->andReturnFalse();
        $this->files->expects('exists')
            ->with('app/Http/Requests/Admin/UserStoreRequest.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('app/Http/Requests/Admin', 0755, true);
        $this->files->expects('put')
            ->with('app/Http/Requests/Admin/UserStoreRequest.php', $this->fixture('form-requests/nested-components.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/nested-components.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Http/Requests/Admin/UserStoreRequest.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_respects_configuration()
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.app_path', 'src/path');

        $this->files->expects('stub')
            ->with('request.stub')
            ->andReturn($this->stub('request.stub'));

        $this->files->expects('exists')
            ->with('src/path/Http/Requests')
            ->andReturns(false);
        $this->files->expects('exists')
            ->with('src/path/Http/Requests/PostStoreRequest.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('src/path/Http/Requests', 0755, true);
        $this->files->expects('put')
            ->with('src/path/Http/Requests/PostStoreRequest.php', $this->fixture('form-requests/form-request-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Http/Requests/PostStoreRequest.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_generates_test_for_controller_tree_using_cached_model()
    {
        $this->files->expects('stub')
            ->with('request.stub')
            ->andReturn($this->stub('request.stub'));

        $this->files->expects('exists')
            ->with('app/Http/Requests')
            ->andReturnFalse();
        $this->files->expects('exists')
            ->with('app/Http/Requests/UserStoreRequest.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('app/Http/Requests', 0755, true);
        $this->files->expects('put')
            ->with('app/Http/Requests/UserStoreRequest.php', $this->fixture('form-requests/reference-cache.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/reference-cache.yaml'));
        $tokens['cache'] = [
            'User' => [
                'email' => 'string',
                'password' => 'string',
            ]
        ];
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Http/Requests/UserStoreRequest.php']], $this->subject->output($tree));
    }
}
