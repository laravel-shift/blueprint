<?php

namespace Tests\Feature\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\ResourceGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use Tests\TestCase;

/**
 * @see ResourceGenerator
 */
class ResourceGeneratorTest extends TestCase
{
    private $blueprint;

    protected $files;

    /** @var ResourceGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ResourceGenerator($this->files);

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
            ->with('resource.stub')
            ->andReturn($this->stub('resource.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     */
    public function output_writes_nothing_without_resource_statements()
    {
        $this->filesystem->expects('stub')
            ->with('resource.stub')
            ->andReturn($this->stub('resource.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/controllers-only.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_resources_for_render_statements()
    {
        $template = $this->stub('resource.stub');
        $this->filesystem->expects('stub')
            ->with('resource.stub')
            ->andReturn($template);

        $this->filesystem->shouldReceive('exists')
            ->twice()
            ->with('app/Http/Resources')
            ->andReturns(false, true);
        $this->filesystem->expects('makeDirectory')
            ->with('app/Http/Resources', 0755, true);

        $this->filesystem->expects('exists')
            ->twice()
            ->with('app/Http/Resources/UserResource.php')
            ->andReturns(false, true);
        $this->filesystem->expects('put')
            ->with('app/Http/Resources/UserResource.php', $this->fixture('resources/user.php'));

        $this->filesystem->expects('exists')
            ->twice()
            ->with('app/Http/Resources/UserCollection.php')
            ->andReturns(false, true);
        $this->filesystem->expects('put')
            ->with('app/Http/Resources/UserCollection.php', $this->fixture('resources/user-collection.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/resource-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Http/Resources/UserCollection.php', 'app/Http/Resources/UserResource.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_namespaced_classes()
    {
        $this->filesystem->expects('stub')
            ->with('resource.stub')
            ->andReturn(file_get_contents('stubs/resource.stub'));

        $this->filesystem->shouldReceive('exists')
            ->with('app/Http/Resources/Api')
            ->andReturns(false, true);
        $this->filesystem->expects('makeDirectory')
            ->with('app/Http/Resources/Api', 0755, true);

        $this->filesystem->expects('exists')
            ->times(3)
            ->with('app/Http/Resources/Api/CertificateResource.php')
            ->andReturns(false, true, true);
        $this->filesystem->expects('put')
            ->with('app/Http/Resources/Api/CertificateResource.php', $this->fixture('resources/certificate.php'));

        $this->filesystem->expects('exists')
            ->with('app/Http/Resources/Api/CertificateCollection.php')
            ->andReturns(false);
        $this->filesystem->expects('put')
            ->with('app/Http/Resources/Api/CertificateCollection.php', $this->fixture('resources/certificate-collection.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/api-routes-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([
            'created' => ['app/Http/Resources/Api/CertificateCollection.php', 'app/Http/Resources/Api/CertificateResource.php'],
        ], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_nested_resource()
    {
        $this->filesystem->expects('stub')
            ->with('resource.stub')
            ->andReturn(file_get_contents('stubs/resource.stub'));

        $this->filesystem->shouldReceive('exists')
            ->with('app/Http/Resources/Api')
            ->andReturns(false, true);
        $this->filesystem->expects('makeDirectory')
            ->with('app/Http/Resources/Api', 0755, true);

        $this->filesystem->expects('exists')
            ->times(3)
            ->with('app/Http/Resources/Api/CertificateResource.php')
            ->andReturns(false, true, true);
        $this->filesystem->expects('put')
            ->with('app/Http/Resources/Api/CertificateResource.php', $this->fixture('resources/certificate-with-nested-resource.php'));

        $this->filesystem->expects('exists')
            ->with('app/Http/Resources/Api/CertificateCollection.php')
            ->andReturns(false);
        $this->filesystem->expects('put')
            ->with('app/Http/Resources/Api/CertificateCollection.php', $this->fixture('resources/certificate-collection.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/resource-nested.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([
            'created' => ['app/Http/Resources/Api/CertificateCollection.php', 'app/Http/Resources/Api/CertificateResource.php'],
        ], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_api_resource_pagination()
    {
        $this->files->expects('stub')
            ->with('resource.stub')
            ->andReturn(file_get_contents('stubs/resource.stub'));

        $this->files->shouldReceive('exists')
            ->with('app/Http/Resources')
            ->andReturns(false, true);
        $this->files->expects('makeDirectory')
            ->with('app/Http/Resources', 0755, true);

        $this->files->expects('exists')
            ->times(3)
            ->with('app/Http/Resources/PostResource.php')
            ->andReturns(false, true, true);
        $this->files->expects('put')
            ->with('app/Http/Resources/PostResource.php', $this->fixture('resources/api-post-resource.php'));

        $this->files->expects('exists')
            ->with('app/Http/Resources/PostCollection.php')
            ->andReturns(false);
        $this->files->expects('put')
            ->with('app/Http/Resources/PostCollection.php', $this->fixture('resources/api-resource-pagination.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/api-resource-pagination.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([
            'created' => ['app/Http/Resources/PostCollection.php', 'app/Http/Resources/PostResource.php'],
        ], $this->subject->output($tree));
    }
}
