<?php

namespace Tests\Feature\Generators\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\MailViewGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use Tests\TestCase;

/**
 * @see MailViewGenerator
 */
class MailViewGeneratorTest extends TestCase
{
    private $blueprint;

    protected $files;

    /** @var MailViewGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new MailViewGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->filesystem->expects('stub')
            ->with('mailView.stub')
            ->andReturn($this->stub('mailView.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     */
    public function output_writes_nothing_tree_without_validate_statements()
    {
        $this->filesystem->expects('stub')
            ->with('mailView.stub')
            ->andReturn($this->stub('mailView.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_nothing_tree_without_view_statements()
    {
        $this->filesystem->expects('stub')
            ->with('mailView.stub')
            ->andReturn($this->stub('mailView.stub'));

        $this->filesystem->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/send-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_mails()
    {
        $this->filesystem->expects('stub')
            ->with('mailView.stub')
            ->andReturn($this->stub('mailView.stub'));

        $this->filesystem->shouldReceive('exists')
            ->with('resources/views/email/my')
            ->andReturns(false, true);
        $this->filesystem->expects('exists')
            ->with('resources/views/email/my/custom-viewFile.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('resources/views/email/my', 0755, true);
        $this->filesystem->expects('put')
            ->with('resources/views/email/my/custom-viewFile.blade.php', $this->fixture('mailables/custom-view-output.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example-custom-view.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['resources/views/email/my/custom-viewFile.blade.php']], $this->subject->output($tree));
    }
    
    /**
     * @test
     */
    public function output_writes_mails_with_typehint()
    {
        $this->app['config']->set('blueprint.use_return_types', true);

        $this->filesystem->expects('stub')
            ->with('mailView.stub')
            ->andReturn($this->stub('mailView.stub'));

        $this->filesystem->shouldReceive('exists')
            ->with('resources/views/email/my')
            ->andReturns(false, true);
        $this->filesystem->expects('exists')
            ->with('resources/views/email/my/custom-viewFile.blade.php')
            ->andReturnFalse();
        $this->filesystem->expects('makeDirectory')
            ->with('resources/views/email/my', 0755, true);
        $this->filesystem->expects('put')
            ->with('resources/views/email/my/custom-viewFile.blade.php', $this->fixture('mailables/custom-view-output-typehint.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example-custom-view.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['resources/views/email/my/custom-viewFile.blade.php']], $this->subject->output($tree));
    }
    
}
