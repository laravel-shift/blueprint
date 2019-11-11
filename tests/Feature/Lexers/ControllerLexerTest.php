<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\ControllerLexer;
use PHPUnit\Framework\TestCase;

class ControllerLexerTest extends TestCase
{
    /**
     * @var ControllerLexer
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ControllerLexer();
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
                        'render' => 'post.show with post'
                    ]
                ],
                'CommentController' => [
                    'index' => [
                        'query' => 'all comments',
                        'render' => 'comment.index with comments'
                    ],
                ]
            ]
        ];

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(2, $actual['controllers']);

        $controller = $actual['controllers']['PostController'];
        $this->assertEquals('PostController', $controller->name());

        $methods = $controller->methods();
        $this->assertCount(2, $methods);

        $this->assertEquals('index', key($methods));
        $this->assertEquals([
            'query' => 'all posts',
            'render' => 'post.index with posts'
        ], current($methods));

        next($methods);
        $this->assertEquals('show', key($methods));
        $this->assertEquals([
            'find' => 'id',
            'render' => 'post.show with post'
        ], current($methods));

        $controller = $actual['controllers']['CommentController'];
        $this->assertEquals('CommentController', $controller->name());

        $methods = $controller->methods();
        $this->assertCount(1, $methods);

        $this->assertEquals('index', key($methods));
        $this->assertEquals([
            'query' => 'all comments',
            'render' => 'comment.index with comments'
        ], current($methods));
    }
}
