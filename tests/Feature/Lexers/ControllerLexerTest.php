<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\ControllerLexer;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Models\Statements\QueryStatement;
use Blueprint\Models\Statements\RenderStatement;
use PHPUnit\Framework\TestCase;

class ControllerLexerTest extends TestCase
{
    /**
     * @var ControllerLexer
     */
    private $subject;

    /**
     * @var \Mockery\MockInterface
     */
    private $statementLexer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statementLexer = \Mockery::mock(StatementLexer::class);

        $this->subject = new ControllerLexer($this->statementLexer);
    }

    /**
     * @test
     */
    public function it_returns_nothing_without_controllers_token()
    {
        $this->assertEquals(['controllers' => []], $this->subject->analyze([]));
    }

    /**
     * @test
     */
    public function it_returns_controllers()
    {
        $tokens = [
            'controllers' => [
                'PostController' => [
                    'index' => [
                        'query' => 'all posts',
                        'render' => 'post.index with posts'
                    ],
                    'show' => [
                        'find' => 'id',
                    ]
                ],
                'CommentController' => [
                    'index' => [
                        'redirect' => 'home'
                    ],
                ]
            ]
        ];

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'query' => 'all posts',
                'render' => 'post.index with posts'
            ])
            ->andReturn(['index-statement-1', 'index-statement-2']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'find' => 'id'
            ])
            ->andReturn(['show-statement-1']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'redirect' => 'home'
            ])
            ->andReturn(['index-statement-1']);

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(2, $actual['controllers']);

        $controller = $actual['controllers']['PostController'];
        $this->assertEquals('PostController', $controller->name());

        $methods = $controller->methods();
        $this->assertCount(2, $methods);

        $this->assertCount(2, $methods['index']);
        $this->assertEquals('index-statement-1', $methods['index'][0]);
        $this->assertEquals('index-statement-2', $methods['index'][1]);

        $this->assertCount(1, $methods['show']);
        $this->assertEquals('show-statement-1', $methods['show'][0]);

        $controller = $actual['controllers']['CommentController'];
        $this->assertEquals('CommentController', $controller->name());

        $methods = $controller->methods();
        $this->assertCount(1, $methods);

        $this->assertCount(1, $methods['index']);
        $this->assertEquals('index-statement-1', $methods['index'][0]);
    }
}
