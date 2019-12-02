<?php

namespace Tests\Feature\Generator\Statements;

use Blueprint\Blueprint;
use Blueprint\Generators\Statements\MailGenerator;
use Blueprint\Lexers\StatementLexer;
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
        $this->files->expects('get')
            ->with('stubs/mail.stub')
            ->andReturn(file_get_contents('stubs/mail.stub'));

        $this->files->shouldNotHaveReceived('put');

        $this->assertEquals([], $this->subject->output(['controllers' => []]));
    }

    /**
     * @test
     */
    public function output_writes_nothing_tree_without_validate_statements()
    {
        $this->files->expects('get')
            ->with('stubs/mail.stub')
            ->andReturn(file_get_contents('stubs/mail.stub'));

        $this->files->shouldNotHaveReceived('put');

        $tokens = $this->blueprint->parse($this->fixture('definitions/render-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function output_writes_mails()
    {
        $this->files->expects('get')
            ->with('stubs/mail.stub')
            ->andReturn(file_get_contents('stubs/mail.stub'));

        $this->files->expects('get')
            ->with('stubs/partials/constructor.stub')
            ->andReturn(file_get_contents('stubs/partials/constructor.stub'));

        $this->files->shouldReceive('exists')
            ->twice()
            ->with('app/Mail')
            ->andReturns(false, true);
        $this->files->expects('exists')
            ->with('app/Mail/ReviewPost.php')
            ->andReturnFalse();
        $this->files->expects('makeDirectory')
            ->with('app/Mail');
        $this->files->expects('put')
            ->with('app/Mail/ReviewPost.php', $this->fixture('mailables/review-post.php'));

        $this->files->expects('exists')
            ->with('app/Mail/PublishedPost.php')
            ->andReturnFalse();
        $this->files->expects('put')
            ->with('app/Mail/PublishedPost.php', $this->fixture('mailables/published-post.php'));

        $tokens = $this->blueprint->parse($this->fixture('definitions/send-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals(['created' => ['app/Mail/ReviewPost.php', 'app/Mail/PublishedPost.php']], $this->subject->output($tree));
    }

    /**
     * @test
     */
    public function it_only_outputs_new_mails()
    {
        $this->files->expects('get')
            ->with('stubs/mail.stub')
            ->andReturn(file_get_contents('stubs/mail.stub'));

        $this->files->expects('exists')
            ->with('app/Mail/ReviewPost.php')
            ->andReturnTrue();
        $this->files->expects('exists')
            ->with('app/Mail/PublishedPost.php')
            ->andReturnTrue();

        $tokens = $this->blueprint->parse($this->fixture('definitions/send-statements.bp'));
        $tree = $this->blueprint->analyze($tokens);

        $this->assertEquals([], $this->subject->output($tree));
    }
}