<?php

namespace Tests\Feature\Generator\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\MailGenerator;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Tree;
use Tests\TestCase;

/**
 * @see MailGenerator
 */
class MailGeneratorTest extends TestCase
{
    private $blueprint;

    private $files;

    /** @var MailGenerator */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = \Mockery::mock();
        $this->subject = new MailGenerator($this->files);

        $this->blueprint = new Blueprint();
        $this->blueprint->registerLexer(new \Blueprint\Lexers\ControllerLexer(new StatementLexer()));
        $this->blueprint->registerGenerator($this->subject);
    }

    /**
     * @test
     */
    public function output_writes_nothing_for_empty_tree()
    {
        $this->files->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(new Tree(['controllers' => []])));
    }

    /**
     * @test
     */
    public function output_writes_nothing_tree_without_validate_statements()
    {
        $this->files->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('drafts/render-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_mails()
    {
        $this->files->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));

        $this->files->expects('stub')
            ->with('constructor.stub')
            ->andReturn($this->stub('constructor.stub'));

        $this->files->shouldReceive('exists')
            ->twice()
            ->with('app/Mail')
            ->andReturns(false, true);
        $this->files->expects('exists')
            ->with('app/Mail/ReviewPost.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('app/Mail', 0755, true);
        $this->files->expects('put')
            ->with('app/Mail/ReviewPost.php', $this->fixture('mailables/review-post.php'));

        $this->files->expects('exists')
            ->with('app/Mail/PublishedPost.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Mail/PublishedPost.php', $this->fixture('mailables/published-post.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/send-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Mail/ReviewPost.php', 'app/Mail/PublishedPost.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_mails()
    {
        $this->files->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));

        $this->files->expects('exists')
            ->with('app/Mail/ReviewPost.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('app/Mail/PublishedPost.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('drafts/send-statements.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_respects_configuration()
    {
        $this->app['config']->set('blueprint.namespace', 'Some\\App');
        $this->app['config']->set('blueprint.app_path', 'src/path');

        $this->files->expects('stub')
            ->with('mail.stub')
            ->andReturn($this->stub('mail.stub'));

        $this->files->expects('exists')
            ->with('src/path/Mail')
            ->andReturnFalse();
        $this->files->expects('exists')
            ->with('src/path/Mail/ReviewPost.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('src/path/Mail', 0755, true);
        $this->files->expects('put')
            ->with('src/path/Mail/ReviewPost.php', $this->fixture('mailables/mail-configured.php'));

        $tokens = $this->blueprint->parse($this->fixture('drafts/readme-example.yaml'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['src/path/Mail/ReviewPost.php']], $this->subject->output($tree));
    }
}
