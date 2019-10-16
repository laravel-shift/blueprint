<?php

namespace Tests\Feature\Parsers;

use Blueprint\Parsers\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Tests\TestCase;

class ParserTest extends TestCase
{
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
        ], Parser::parse($blueprint));
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
        ], Parser::parse($blueprint));
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
        ], Parser::parse($blueprint));
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
        ], Parser::parse($blueprint));
    }

    /**
     * @test
     */
    public function it_throws_a_custom_error_when_parsing_fails()
    {
        $this->expectException(ParseException::class);

        $blueprint = $this->fixture('definitions/invalid.bp');

        Parser::parse($blueprint);
    }
}