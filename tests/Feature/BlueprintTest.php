<?php

namespace Tests\Feature;

use Blueprint\Blueprint;
use Symfony\Component\Yaml\Exception\ParseException;
use Tests\TestCase;

class BlueprintTest extends TestCase
{
    /**
     * @var Blueprint
     */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->subject = new Blueprint();
    }

    /**
     * @test
     */
    public function it_parses_models()
    {
        $blueprint = $this->fixture('definitions/models-only.bp');

        $this->assertEquals([
            'models' => [
                'ModelOne' => [
                    'column' => 'datatype modifier',
                ],
                'ModelTwo' => [
                    'column' => 'datatype',
                    'another_column' => 'datatype modifier',
                ],
            ],
        ], $this->subject->parse($blueprint));
    }

    /**
     * @test
     */
    public function it_parses_controllers()
    {
        $blueprint = $this->fixture('definitions/controllers-only.bp');

        $this->assertEquals([
            'controllers' => [
                'UserController' => [
                    'index' => [
                        'action' => 'detail'
                    ],
                    'create' => [
                        'action' => 'additional detail'
                    ],
                ],
                'RoleController' => [
                    'index' => [
                        'action' => 'detail',
                        'another_action' => 'so much detail',
                    ],
                ],
            ],
        ], $this->subject->parse($blueprint));
    }

    /**
     * @test
     */
    public function it_parses_shorthands()
    {
        $blueprint = $this->fixture('definitions/shorthands.bp');

        $this->assertEquals([
            'models' => [
                'Name' => [
                    'id' => 'id',
                    'timestamps' => 'timestamps',
                ],
            ],
        ], $this->subject->parse($blueprint));
    }

    /**
     * @test
     */
    public function it_parses_the_readme_example()
    {
        $blueprint = $this->fixture('definitions/readme-example.bp');

        $this->assertEquals([
            'models' => [
                'Post' => [
                    'id' => 'id',
                    'title' => 'string',
                    'content' => 'bigtext',
                    'published_at' => 'nullable timestamp',
                    'timestamps' => 'timestamps'
                ],
            ],
            'controllers' => [
                'Post' => [
                    'index' => [
                        'query' => 'all posts',
                        'render' => 'post.index with posts',
                    ],
                    'store' => [
                        'validate' => 'title, content',
                        'save' => 'post',
                        'send' => 'ReviewNotifcation to post.author',
                        'queue' => 'SyncMedia',
                        'flash' => 'post.title',
                        'redirect' => 'post.index',
                    ],
                ],
            ],
        ], $this->subject->parse($blueprint));
    }

    /**
     * @test
     */
    public function it_throws_a_custom_error_when_parsing_fails()
    {
        $this->expectException(ParseException::class);

        $blueprint = $this->fixture('definitions/invalid.bp');

        $this->subject->parse($blueprint);
    }

    /**
     * @test
     */
    public function analyze_return_default_tree_for_empty_tokens()
    {
        $tokens = [];

        $this->assertEquals([
            'models' => [],
            'controllers' => []
        ],
            $this->subject->analyze($tokens));
    }

    /**
     * @test
     */
    public function analyze_uses_register_lexers_to_analyze_tokens()
    {
        $lexer = \Mockery::mock();
        $tokens = ['tokens' => ['are', 'here']];
        $lexer->expects('analyze')
            ->with($tokens)
            ->andReturn(['mock' => 'lexer']);

        $this->subject->registerLexer($lexer);

        $this->assertEquals([
            'models' => [],
            'controllers' => [],
            'mock' => 'lexer'
        ], $this->subject->analyze($tokens));
    }

    /**
     * @test
     */
    public function generate_uses_register_generators_to_generate_code()
    {
        $generator = \Mockery::mock();
        $tree = ['branch' => ['code', 'attributes']];
        $generator->expects('generate')
            ->with($tree);

        $this->subject->registerGenerator($generator);

        $this->subject->generate($tree);
    }
}