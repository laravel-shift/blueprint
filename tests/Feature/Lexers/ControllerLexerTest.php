<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\ControllerLexer;
use Blueprint\Lexers\StatementLexer;
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
                        'query' => 'all:posts',
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
                'query' => 'all:posts',
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

    /**
     * @test
     */
    public function it_returns_a_web_resource_controller()
    {
        $tokens = [
            'controllers' => [
                'Comment' => [
                    'resource' => 'web'
                ]
            ]
        ];

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'query' => 'all:comments',
                'render' => 'comment.index with comments'
            ])
            ->andReturn(['index-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'comment.create'
            ])
            ->andReturn(['create-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'validate' => 'comment',
                'save' => 'comment',
                'flash' => 'comment.id',
                'redirect' => 'comment.index'
            ])
            ->andReturn(['store-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'comment.show with:comment'
            ])
            ->andReturn(['show-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'comment.edit with:comment'
            ])
            ->andReturn(['edit-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'validate' => 'comment',
                'update' => 'comment',
                'flash' => 'comment.id',
                'redirect' => 'comment.index'
            ])
            ->andReturn(['update-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'delete' => 'comment',
                'redirect' => 'comment.index'
            ])
            ->andReturn(['destroy-statements']);

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual['controllers']);

        $controller = $actual['controllers']['Comment'];
        $this->assertEquals('CommentController', $controller->className());

        $methods = $controller->methods();
        $this->assertCount(7, $methods);

        $this->assertCount(1, $methods['index']);
        $this->assertEquals('index-statements', $methods['index'][0]);
        $this->assertCount(1, $methods['create']);
        $this->assertEquals('create-statements', $methods['create'][0]);
        $this->assertCount(1, $methods['store']);
        $this->assertEquals('store-statements', $methods['store'][0]);
        $this->assertCount(1, $methods['show']);
        $this->assertEquals('show-statements', $methods['show'][0]);
        $this->assertCount(1, $methods['edit']);
        $this->assertEquals('edit-statements', $methods['edit'][0]);
        $this->assertCount(1, $methods['update']);
        $this->assertEquals('update-statements', $methods['update'][0]);
        $this->assertCount(1, $methods['destroy']);
        $this->assertEquals('destroy-statements', $methods['destroy'][0]);
    }

    /**
     * @test
     */
    public function it_returns_an_api_resource_controller()
    {
        $tokens = [
            'controllers' => [
                'Comment' => [
                    'resource' => 'api.index, api.store, api.show, api.update'
                ]
            ]
        ];

        $this->statementLexer->expects('analyze')
            ->with([
                'query' => 'all:comments',
                'resource' => 'collection:comments'
            ])
            ->andReturn(['api-index-statements']);
        $this->statementLexer->expects('analyze')
            ->with([
                'validate' => 'comment',
                'save' => 'comment',
                'resource' => 'comment'
            ])
            ->andReturn(['api-store-statements']);
        $this->statementLexer->expects('analyze')
            ->with([
                'resource' => 'comment'
            ])
            ->andReturn(['api-show-statements']);
        $this->statementLexer->expects('analyze')
            ->with([
                'validate' => 'comment',
                'update' => 'comment',
                'resource' => 'comment'
            ])
            ->andReturn(['api-update-statements']);

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual['controllers']);

        $controller = $actual['controllers']['Comment'];
        $this->assertEquals('CommentController', $controller->className());
        $this->assertTrue($controller->isApiResource());

        $methods = $controller->methods();
        $this->assertCount(4, $methods);

        $this->assertCount(1, $methods['index']);
        $this->assertEquals('api-index-statements', $methods['index'][0]);
        $this->assertCount(1, $methods['store']);
        $this->assertEquals('api-store-statements', $methods['store'][0]);
        $this->assertCount(1, $methods['show']);
        $this->assertEquals('api-show-statements', $methods['show'][0]);
        $this->assertCount(1, $methods['update']);
        $this->assertEquals('api-update-statements', $methods['update'][0]);
    }

    /**
     * @test
     */
    public function it_returns_a_specific_resource_controller()
    {
        $tokens = [
            'controllers' => [
                'User' => [
                    'resource' => 'index, edit, update, destroy'
                ]
            ]
        ];

        $this->statementLexer->expects('analyze')
            ->with([
                'query' => 'all:users',
                'render' => 'user.index with users'
            ])
            ->andReturn(['index-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'user.edit with:user'
            ])
            ->andReturn(['edit-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'validate' => 'user',
                'update' => 'user',
                'flash' => 'user.id',
                'redirect' => 'user.index'
            ])
            ->andReturn(['update-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'delete' => 'user',
                'redirect' => 'user.index'
            ])
            ->andReturn(['destroy-statements']);

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual['controllers']);

        $controller = $actual['controllers']['User'];
        $this->assertEquals('UserController', $controller->className());

        $methods = $controller->methods();
        $this->assertCount(4, $methods);

        $this->assertCount(1, $methods['index']);
        $this->assertEquals('index-statements', $methods['index'][0]);
        $this->assertCount(1, $methods['edit']);
        $this->assertEquals('edit-statements', $methods['edit'][0]);
        $this->assertCount(1, $methods['update']);
        $this->assertEquals('update-statements', $methods['update'][0]);
        $this->assertCount(1, $methods['destroy']);
        $this->assertEquals('destroy-statements', $methods['destroy'][0]);
    }

    /**
     * @test
     */
    public function it_returns_a_resource_controller_with_overrides()
    {
        $tokens = [
            'controllers' => [
                'User' => [
                    'resource' => 'index, show',
                    'index' => [
                        'query' => 'all',
                        'respond' => 'users',
                    ],
                    'custom' => [
                        'statement' => 'expression',
                    ],
                ]
            ]
        ];

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'query' => 'all',
                'respond' => 'users'
            ])
            ->andReturn(['custom-index-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'user.show with:user'
            ])
            ->andReturn(['show-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'statement' => 'expression'
            ])
            ->andReturn(['custom-statements']);

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual['controllers']);

        $controller = $actual['controllers']['User'];
        $this->assertEquals('UserController', $controller->className());

        $methods = $controller->methods();
        $this->assertCount(3, $methods);
        $this->assertCount(1, $methods['index']);
        $this->assertEquals('custom-index-statements', $methods['index'][0]);
        $this->assertCount(1, $methods['show']);
        $this->assertEquals('show-statements', $methods['show'][0]);
        $this->assertCount(1, $methods['custom']);
        $this->assertEquals('custom-statements', $methods['custom'][0]);
    }

    /**
     * @test
     */
    public function it_returns_a_resource_controllers_with_api_flag_set()
    {
        $tokens = [
            'controllers' => [
                'Page' => [
                    'resource' => 'web',
                ],
                'File' => [
                    'resource' => 'api',
                ],
                'Category' => [
                    'resource' => 'web',
                ],
                'Gallery' => [
                    'resource' => 'api',
                ],
            ]
        ];

        $this->statementLexer->shouldReceive('analyze');

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(4, $actual['controllers']);

        $controller = $actual['controllers']['Page'];
        $this->assertEquals('PageController', $controller->className());
        $this->assertCount(7, $controller->methods());
        $this->assertFalse($controller->isApiResource());

        $controller = $actual['controllers']['File'];
        $this->assertEquals('FileController', $controller->className());
        $this->assertCount(5, $controller->methods());
        $this->assertTrue($controller->isApiResource());

        $controller = $actual['controllers']['Category'];
        $this->assertEquals('CategoryController', $controller->className());
        $this->assertCount(7, $controller->methods());
        $this->assertFalse($controller->isApiResource());

        $controller = $actual['controllers']['Gallery'];
        $this->assertEquals('GalleryController', $controller->className());
        $this->assertCount(5, $controller->methods());
        $this->assertTrue($controller->isApiResource());
    }

    /**
     * @test
     */
    public function it_returns_an_invokable_controller()
    {
        $tokens = [
            'controllers' => [
                'Report' => [
                    '__invoke' => [
                        'render' => 'report'
                    ]
                ]
            ]
        ];

        $this->statementLexer->shouldReceive('analyze');

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual['controllers']);

        $controller = $actual['controllers']['Report'];
        $this->assertEquals('ReportController', $controller->className());
        $this->assertCount(1, $controller->methods());
        $this->assertFalse($controller->isApiResource());
    }
}
