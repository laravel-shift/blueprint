<?php

namespace Tests\Feature;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Contracts\Lexer;
use Symfony\Component\Yaml\Exception\ParseException;
use Tests\TestCase;

/**
 * @see Blueprint
 */
class BlueprintTest extends TestCase
{
    /**
     * @var Blueprint
     */
    private $subject;

    protected function setUp(): void
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
                    'softdeletes' => 'softDeletes',
                    'id' => 'id',
                    'timestamps' => 'timestamps',
                ],
            ],
        ], $this->subject->parse($blueprint));
    }

    /**
     * @test
     */
    public function it_parses_shorthands_with_timezones()
    {
        $blueprint = $this->fixture('definitions/with-timezones.bp');

        $this->assertEquals([
            'models' => [
                'Comment' => [
                    'softdeletestz' => 'softDeletesTz',
                    'timestampstz' => 'timestampstz',
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
                    'title' => 'string:400',
                    'content' => 'longtext',
                    'published_at' => 'nullable timestamp',
                ],
            ],
            'controllers' => [
                'Post' => [
                    'index' => [
                        'query' => 'all:posts',
                        'render' => 'post.index with:posts',
                    ],
                    'store' => [
                        'validate' => 'title, content',
                        'save' => 'post',
                        'send' => 'ReviewNotification to:post.author with:post',
                        'dispatch' => 'SyncMedia with:post',
                        'fire' => 'NewPost with:post',
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
        $lexer = \Mockery::mock(Lexer::class);
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
    public function generate_uses_registered_generators_and_returns_generated_files()
    {
        $generatorOne = \Mockery::mock(Generator::class);
        $tree = ['branch' => ['code', 'attributes']];
        $generatorOne->expects('output')
            ->with($tree)
            ->andReturn([
                'created' => ['one/new.php'],
                'updated' => ['one/existing.php'],
                'deleted' => ['one/trashed.php']
            ]);

        $generatorTwo = \Mockery::mock(Generator::class);
        $generatorTwo->expects('output')
            ->with($tree)
            ->andReturn([
                'created' => ['two/new.php'],
                'updated' => ['two/existing.php'],
                'deleted' => ['two/trashed.php']
            ]);

        $this->subject->registerGenerator($generatorOne);
        $this->subject->registerGenerator($generatorTwo);

        $this->assertEquals([
            'created' => ['one/new.php', 'two/new.php'],
            'updated' => ['one/existing.php', 'two/existing.php'],
            'deleted' => ['one/trashed.php', 'two/trashed.php'],
        ], $this->subject->generate($tree));
    }
}