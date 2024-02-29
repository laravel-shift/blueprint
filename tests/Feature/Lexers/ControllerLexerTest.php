<?php

namespace Tests\Feature\Lexers;

use Blueprint\Lexers\ControllerLexer;
use Blueprint\Lexers\StatementLexer;
use Blueprint\Models\Policy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ControllerLexerTest extends TestCase
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

    #[Test]
    public function it_returns_nothing_without_controllers_token(): void
    {
        $this->assertEquals([
            'controllers' => [],
            'policies' => [],
        ], $this->subject->analyze([]));
    }

    #[Test]
    public function it_returns_controllers(): void
    {
        $tokens = [
            'controllers' => [
                'PostController' => [
                    'index' => [
                        'query' => 'all:posts',
                        'render' => 'post.index with:posts',
                    ],
                    'show' => [
                        'find' => 'id',
                    ],
                ],
                'CommentController' => [
                    'index' => [
                        'redirect' => 'home',
                    ],
                ],
            ],
        ];

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'query' => 'all:posts',
                'render' => 'post.index with:posts',
            ])
            ->andReturn(['index-statement-1', 'index-statement-2']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'find' => 'id',
            ])
            ->andReturn(['show-statement-1']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'redirect' => 'home',
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

    #[Test]
    public function it_returns_a_web_resource_controller(): void
    {
        $tokens = [
            'controllers' => [
                'Comment' => [
                    'resource' => 'web',
                ],
            ],
        ];

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'query' => 'all:comments',
                'render' => 'comment.index with:comments',
            ])
            ->andReturn(['index-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'comment.create',
            ])
            ->andReturn(['create-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'validate' => 'comment',
                'save' => 'comment',
                'flash' => 'comment.id',
                'redirect' => 'comments.index',
            ])
            ->andReturn(['store-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'comment.show with:comment',
            ])
            ->andReturn(['show-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'comment.edit with:comment',
            ])
            ->andReturn(['edit-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'validate' => 'comment',
                'update' => 'comment',
                'flash' => 'comment.id',
                'redirect' => 'comments.index',
            ])
            ->andReturn(['update-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'delete' => 'comment',
                'redirect' => 'comments.index',
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

    #[Test]
    public function it_returns_an_api_resource_controller(): void
    {
        $tokens = [
            'controllers' => [
                'Comment' => [
                    'resource' => 'api.index, api.store, api.show, api.update',
                ],
            ],
        ];

        $this->statementLexer->expects('analyze')
            ->with([
                'query' => 'all:comments',
                'resource' => 'collection:comments',
            ])
            ->andReturn(['api-index-statements']);
        $this->statementLexer->expects('analyze')
            ->with([
                'validate' => 'comment',
                'save' => 'comment',
                'resource' => 'comment',
            ])
            ->andReturn(['api-store-statements']);
        $this->statementLexer->expects('analyze')
            ->with([
                'resource' => 'comment',
            ])
            ->andReturn(['api-show-statements']);
        $this->statementLexer->expects('analyze')
            ->with([
                'validate' => 'comment',
                'update' => 'comment',
                'resource' => 'comment',
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

    #[Test]
    public function it_returns_a_specific_resource_controller(): void
    {
        $tokens = [
            'controllers' => [
                'User' => [
                    'resource' => 'index, edit, update, destroy',
                ],
            ],
        ];

        $this->statementLexer->expects('analyze')
            ->with([
                'query' => 'all:users',
                'render' => 'user.index with:users',
            ])
            ->andReturn(['index-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'user.edit with:user',
            ])
            ->andReturn(['edit-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'validate' => 'user',
                'update' => 'user',
                'flash' => 'user.id',
                'redirect' => 'users.index',
            ])
            ->andReturn(['update-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'delete' => 'user',
                'redirect' => 'users.index',
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

    #[Test]
    public function it_returns_a_resource_controller_with_overrides(): void
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
                ],
            ],
        ];

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'query' => 'all',
                'respond' => 'users',
            ])
            ->andReturn(['custom-index-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'render' => 'user.show with:user',
            ])
            ->andReturn(['show-statements']);

        $this->statementLexer->shouldReceive('analyze')
            ->with([
                'statement' => 'expression',
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

    #[Test]
    public function it_returns_a_resource_controllers_with_api_flag_set(): void
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
            ],
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

    #[Test]
    public function it_returns_an_invokable_controller(): void
    {
        $tokens = [
            'controllers' => [
                'Report' => [
                    '__invoke' => [
                        'render' => 'report',
                    ],
                ],
            ],
        ];

        $this->statementLexer->shouldReceive('analyze');

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual['controllers']);

        $controller = $actual['controllers']['Report'];
        $this->assertEquals('ReportController', $controller->className());
        $this->assertCount(1, $controller->methods());
        $this->assertFalse($controller->isApiResource());
    }

    #[Test]
    public function it_returns_an_authorized_controller_with_all_policies(): void
    {
        $tokens = [
            'controllers' => [
                'Report' => [
                    'meta' => [
                        'policies' => true,
                    ],
                ],
            ],
        ];

        $this->statementLexer->shouldReceive('analyze');

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual['controllers']);
        $this->assertCount(1, $actual['policies']);

        $controller = $actual['controllers']['Report'];
        $this->assertInstanceOf(Policy::class, $controller->policy());
        $this->assertEquals(Policy::$supportedMethods, $controller->policy()->methods());
    }

    #[Test]
    public function it_returns_an_authorized_controller_with_specific_policies(): void
    {
        $tokens = [
            'controllers' => [
                'Report' => [
                    'meta' => [
                        'policies' => 'index,show',
                    ],
                ],
            ],
        ];

        $this->statementLexer->shouldReceive('analyze');

        $actual = $this->subject->analyze($tokens);

        $this->assertCount(1, $actual['controllers']);
        $this->assertCount(1, $actual['policies']);

        $controller = $actual['controllers']['Report'];
        $this->assertInstanceOf(Policy::class, $controller->policy());
        $this->assertEquals(['viewAny', 'view'], $controller->policy()->methods());
    }
}
