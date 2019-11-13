<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\StatementLexer;
use Blueprint\Models\Statements\DispatchStatement;
use Blueprint\Models\Statements\EventStatement;
use Blueprint\Models\Statements\MailStatement;
use Blueprint\Models\Statements\RenderStatement;
use PHPUnit\Framework\TestCase;

/**
 * @see StatementLexer
 */
class StatementLexerTest extends TestCase
{
    /**
     * @var StatementLexer
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new StatementLexer();
    }

    /**
     * @test
     */
    public function it_returns_nothing_without_statements_token()
    {
        $this->assertEquals([], $this->subject->analyze([]));
    }

    /**
     * @test
     */
    public function it_returns_a_render_statement()
    {
        $tokens = [
            'render' => 'post.index'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(RenderStatement::class, $actual[0]);

        $this->assertEquals('post.index', $actual[0]->view());
        $this->assertSame([], $actual[0]->data());
    }

    /**
     * @test
     */
    public function it_returns_a_render_statement_with_data()
    {
        $tokens = [
            'render' => 'post.index with:foo,bar,baz'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(RenderStatement::class, $actual[0]);

        $this->assertEquals('post.index', $actual[0]->view());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
    }

    /**
     * @test
     */
    public function it_returns_an_event_statement()
    {
        $tokens = [
            'fire' => 'SomeEvent'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(EventStatement::class, $actual[0]);

        $this->assertEquals('SomeEvent', $actual[0]->event());
        $this->assertSame([], $actual[0]->data());
    }

    /**
     * @test
     */
    public function it_returns_an_event_statement_with_data()
    {
        $tokens = [
            'fire' => 'some.event with:foo, bar,  baz'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(EventStatement::class, $actual[0]);

        $this->assertEquals('some.event', $actual[0]->event());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
    }

    /**
     * @test
     */
    public function it_returns_a_dispatch_statement()
    {
        $tokens = [
            'dispatch' => 'ProcessPodcast'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(DispatchStatement::class, $actual[0]);

        $this->assertEquals('ProcessPodcast', $actual[0]->job());
        $this->assertSame([], $actual[0]->data());
    }

    /**
     * @test
     */
    public function it_returns_a_dispatch_statement_with_data()
    {
        $tokens = [
            'dispatch' => 'ProcessPodcast with:foo, bar,        baz'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(DispatchStatement::class, $actual[0]);

        $this->assertEquals('ProcessPodcast', $actual[0]->job());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
    }

    /**
     * @test
     */
    public function it_returns_a_mail_statement()
    {
        $tokens = [
            'send' => 'ReviewMail'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(MailStatement::class, $actual[0]);

        $this->assertEquals('ReviewMail', $actual[0]->mail());
        $this->assertNull($actual[0]->to());
        $this->assertSame([], $actual[0]->data());
    }

    /**
     * @test
     */
    public function it_returns_a_mail_statement_sent_to()
    {
        $tokens = [
            'send' => 'ReviewMail to:post.author'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(MailStatement::class, $actual[0]);

        $this->assertEquals('ReviewMail', $actual[0]->mail());
        $this->assertEquals('post.author', $actual[0]->to());
        $this->assertSame([], $actual[0]->data());
    }

    /**
     * @test
     */
    public function it_returns_a_mail_statement_with_data()
    {
        $tokens = [
            'send' => 'ReviewMail to:post.author with:foo, bar, baz'
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual);
        $this->assertInstanceOf(MailStatement::class, $actual[0]);

        $this->assertEquals('ReviewMail', $actual[0]->mail());
        $this->assertEquals('post.author', $actual[0]->to());
        $this->assertEquals(['foo', 'bar', 'baz'], $actual[0]->data());
    }
}