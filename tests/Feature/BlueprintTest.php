<?php

namespace Tests\Feature;

use Blueprint\Blueprint;
use Blueprint\Contracts\Generator;
use Blueprint\Contracts\Lexer;
use Blueprint\Tree;
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
        $blueprint = $this->fixture('drafts/models-only.yaml');

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

    public function it_parses_seeders()
    {
        $blueprint = $this->fixture('drafts/seeders.yaml');

        $this->assertEquals([
            'models' => [
                'Post' => [
                    'title' => 'string:400',
                    'content' => 'longtext',
                    'published_at' => 'nullable timestamp',
                ],
                'Comment' => [
                    'post_id' => 'id',
                    'content' => 'longtext',
                    'approved' => 'boolean',
                    'user_id' => 'id',
                ],
            ],
            'seeders' => 'Post, Comment',
        ], $this->subject->parse($blueprint));
    }

    /**
     * @test
     */
    public function it_parses_controllers()
    {
        $blueprint = $this->fixture('drafts/controllers-only.yaml');

        $this->assertEquals([
            'controllers' => [
                'UserController' => [
                    'index' => [
                        'action' => 'detail',
                    ],
                    'create' => [
                        'action' => 'additional detail',
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
        $blueprint = $this->fixture('drafts/shorthands.yaml');

        $this->assertEquals([
            'models' => [
                'Name' => [
                    'softdeletes' => 'softDeletes',
                    'id' => 'id',
                    'timestamps' => 'timestamps',
                ],
            ],
            'controllers' => [
                'Context' => [
                    'resource' => 'web',
                ],
            ],
        ], $this->subject->parse($blueprint));
    }

    /**
     * @test
     */
    public function it_parses_uuid_shorthand()
    {
        $blueprint = $this->fixture('drafts/uuid-shorthand.yaml');

        $this->assertEquals([
            'models' => [
                'Person' => [
                    'id' => 'uuid primary',
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
        $blueprint = $this->fixture('drafts/with-timezones.yaml');

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
    public function it_parses_longhands()
    {
        $blueprint = $this->fixture('drafts/longhands.yaml');

        $this->assertEquals([
            'models' => [
                'Proper' => [
                    'id' => 'id',
                    'softdeletes' => 'softDeletes',
                    'timestamps' => 'timestamps',
                ],
                'Lower' => [
                    'id' => 'id',
                    'softdeletes' => 'softdeletes',
                    'timestampstz' => 'timestampstz',
                ],
                'Timezone' => [
                    'softdeletestz' => 'softdeletestz',
                    'timestampstz' => 'timestampsTz',
                ],
            ],
        ], $this->subject->parse($blueprint));
    }

    /**
     * @test
     */
    public function it_parses_resource_shorthands()
    {
        $blueprint = $this->fixture('drafts/with-timezones.yaml');

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
        $blueprint = $this->fixture('drafts/readme-example.yaml');

        $this->assertEquals([
            'models' => [
                'Post' => [
                    'title' => 'string:400',
                    'content' => 'longtext',
                    'published_at' => 'nullable timestamp',
                    'author_id' => 'id:user',
                ],
            ],
            'controllers' => [
                'Post' => [
                    'index' => [
                        'query' => 'all',
                        'render' => 'post.index with:posts',
                    ],
                    'store' => [
                        'validate' => 'title, content, author_id',
                        'save' => 'post',
                        'send' => 'ReviewPost to:post.author.email with:post',
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
    public function it_parses_the_readme_example_with_different_platform_eols()
    {
        $definition = $this->fixture('drafts/readme-example.yaml');

        $LF = "\n";
        $CR = "\r";
        $CRLF = "\r\n";

        $definition_mac_eol = str_replace($LF, $CR, $definition);
        $definition_windows_eol = str_replace($LF, $CRLF, $definition);

        $expected = [
            'models' => [
                'Post' => [
                    'title' => 'string:400',
                    'content' => 'longtext',
                    'published_at' => 'nullable timestamp',
                    'author_id' => 'id:user',
                ],
            ],
            'controllers' => [
                'Post' => [
                    'index' => [
                        'query' => 'all',
                        'render' => 'post.index with:posts',
                    ],
                    'store' => [
                        'validate' => 'title, content, author_id',
                        'save' => 'post',
                        'send' => 'ReviewPost to:post.author.email with:post',
                        'dispatch' => 'SyncMedia with:post',
                        'fire' => 'NewPost with:post',
                        'flash' => 'post.title',
                        'redirect' => 'post.index',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->parse($definition_mac_eol));
        $this->assertEquals($expected, $this->subject->parse($definition_windows_eol));
    }

    /**
     * @test
     */
    public function it_parses_yaml_with_dashed_syntax()
    {
        $definition = $this->fixture('drafts/readme-example-dashes.yaml');

        $expected = [
            'models' => [
                'Post' => [
                    'title' => 'string:400',
                    'content' => 'longtext',
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
                        'redirect' => 'post.index',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $this->subject->parse($definition));
    }

    /**
     * @test
     */
    public function it_allows_parsing_without_stripping_dashes()
    {
        $sequence = [
            'numbers' => range(3, 11),
        ];

        $this->assertEquals($sequence, $this->subject->parse($this->subject->dump($sequence), false));
    }

    /**
     * @test
     */
    public function it_throws_a_custom_error_when_parsing_fails()
    {
        $this->expectException(ParseException::class);

        $blueprint = $this->fixture('drafts/invalid.yaml');

        $this->subject->parse($blueprint);
    }

    /**
     * @test
     */
    public function analyze_return_default_tree_for_empty_tokens()
    {
        $tokens = [];

        $this->assertEquals(
            [
                'models' => [],
                'controllers' => [],
            ],
            $this->subject->analyze($tokens)->toArray()
        );
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
            'mock' => 'lexer',
        ], $this->subject->analyze($tokens)->toArray());
    }

    /**
     * @test
     */
    public function generate_uses_registered_generators_and_returns_generated_files()
    {
        $generatorOne = \Mockery::mock(Generator::class);
        $tree = new Tree(['branch' => ['code', 'attributes']]);

        $generatorOne->expects('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['one/new.php'],
                'updated' => ['one/existing.php'],
                'deleted' => ['one/trashed.php'],
            ]);

        $generatorOne->expects('types')
            ->andReturn([
                'some',
                'types',
            ]);

        $generatorTwo = \Mockery::mock(Generator::class);
        $generatorTwo->expects('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['two/new.php'],
                'updated' => ['two/existing.php'],
                'deleted' => ['two/trashed.php'],
            ]);

        $generatorTwo->expects('types')
            ->andReturn([
                'some',
                'types',
            ]);

        $this->subject->registerGenerator($generatorOne);
        $this->subject->registerGenerator($generatorTwo);

        $this->assertEquals([
            'created' => ['one/new.php', 'two/new.php'],
            'updated' => ['one/existing.php', 'two/existing.php'],
            'deleted' => ['one/trashed.php', 'two/trashed.php'],
        ], $this->subject->generate($tree));
    }

    /**
     * @test
     */
    public function generate_uses_swapped_generator_and_returns_generated_files()
    {
        $generatorOne = \Mockery::mock(Generator::class);
        $tree = new Tree(['branch' => ['code', 'attributes']]);

        $generatorOne->expects('output')->never();

        $generatorSwap = \Mockery::mock(Generator::class);
        $generatorSwap->expects('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['swapped/new.php'],
                'updated' => ['swapped/existing.php'],
                'deleted' => ['swapped/trashed.php'],
            ]);

        $generatorSwap->expects('types')
            ->andReturn([
                'some',
                'types',
            ]);

        $this->subject->registerGenerator($generatorOne);
        $this->subject->swapGenerator(get_class($generatorOne), $generatorSwap);

        $this->assertEquals([
            'created' => ['swapped/new.php'],
            'updated' => ['swapped/existing.php'],
            'deleted' => ['swapped/trashed.php'],
        ], $this->subject->generate($tree));
    }

    /**
     * @test
     */
    public function generate_only_one_specific_type()
    {
        $generatorFoo = \Mockery::mock(Generator::class);
        $tree = new Tree(['branch' => ['code', 'attributes']]);

        $only = ['bar'];
        $skip = [];

        $generatorFoo->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['foo.php'],
            ]);

        $generatorFoo->shouldReceive('types')
            ->andReturn(['foo']);

        $generatorBar = \Mockery::mock(Generator::class);
        $generatorBar->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['bar.php'],
            ]);

        $generatorBar->shouldReceive('types')
            ->andReturn(['bar']);

        $generatorBaz = \Mockery::mock(Generator::class);
        $generatorBaz->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['baz.php'],
            ]);

        $generatorBaz->shouldReceive('types')
            ->andReturn(['baz']);

        $this->subject->registerGenerator($generatorFoo);
        $this->subject->registerGenerator($generatorBar);
        $this->subject->registerGenerator($generatorBaz);

        $actual = $this->subject->generate($tree, $only, $skip);

        $this->assertEquals([
            'created' => ['bar.php'],
        ], $actual);
    }

    /**
     * @test
     */
    public function generate_only_specific_types()
    {
        $generatorFoo = \Mockery::mock(Generator::class);
        $tree = new Tree(['branch' => ['code', 'attributes']]);

        $only = ['foo', 'bar'];
        $skip = [];

        $generatorFoo->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['foo.php'],
            ]);

        $generatorFoo->shouldReceive('types')
            ->andReturn(['foo']);

        $generatorBar = \Mockery::mock(Generator::class);
        $generatorBar->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['bar.php'],
            ]);

        $generatorBar->shouldReceive('types')
            ->andReturn(['bar']);

        $generatorBaz = \Mockery::mock(Generator::class);
        $generatorBaz->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['baz.php'],
            ]);

        $generatorBaz->shouldReceive('types')
            ->andReturn(['baz']);

        $this->subject->registerGenerator($generatorFoo);
        $this->subject->registerGenerator($generatorBar);
        $this->subject->registerGenerator($generatorBaz);

        $actual = $this->subject->generate($tree, $only, $skip);

        $this->assertEquals([
            'created' => ['foo.php', 'bar.php'],
        ], $actual);
    }

    /**
     * @test
     */
    public function generate_should_skip_one_specific_type()
    {
        $generatorFoo = \Mockery::mock(Generator::class);
        $tree = new Tree(['branch' => ['code', 'attributes']]);

        $only = [];
        $skip = ['bar'];

        $generatorFoo->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['foo.php'],
            ]);

        $generatorFoo->shouldReceive('types')
            ->andReturn(['foo']);

        $generatorBar = \Mockery::mock(Generator::class);
        $generatorBar->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['bar.php'],
            ]);

        $generatorBar->shouldReceive('types')
            ->andReturn(['bar']);

        $generatorBaz = \Mockery::mock(Generator::class);
        $generatorBaz->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['baz.php'],
            ]);

        $generatorBaz->shouldReceive('types')
            ->andReturn(['baz']);

        $this->subject->registerGenerator($generatorFoo);
        $this->subject->registerGenerator($generatorBar);
        $this->subject->registerGenerator($generatorBaz);

        $actual = $this->subject->generate($tree, $only, $skip);

        $this->assertEquals([
            'created' => ['foo.php', 'baz.php'],
        ], $actual);
    }

    /**
     * @test
     */
    public function generate_should_skip_specific_types()
    {
        $generatorFoo = \Mockery::mock(Generator::class);
        $tree = new Tree(['branch' => ['code', 'attributes']]);

        $only = [];
        $skip = ['bar', 'baz'];

        $generatorFoo->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['foo.php'],
            ]);

        $generatorFoo->shouldReceive('types')
            ->andReturn(['foo']);

        $generatorBar = \Mockery::mock(Generator::class);
        $generatorBar->shouldReceive('output')
            ->with($tree)
            ->andReturn([
                'created' => ['bar.php'],
            ]);

        $generatorBar->shouldReceive('types')
            ->andReturn(['bar']);

        $generatorBaz = \Mockery::mock(Generator::class);
        $generatorBaz->shouldReceive('output')
            ->with($tree, false)
            ->andReturn([
                'created' => ['baz.php'],
            ]);

        $generatorBaz->shouldReceive('types')
            ->andReturn(['baz']);

        $this->subject->registerGenerator($generatorFoo);
        $this->subject->registerGenerator($generatorBar);
        $this->subject->registerGenerator($generatorBaz);

        $actual = $this->subject->generate($tree, $only, $skip);

        $this->assertEquals([
            'created' => ['foo.php'],
        ], $actual);
    }

    /**
     * @test
     * @dataProvider namespacesDataProvider
     */
    public function relative_namespace_removes_namespace_prefix_from_reference($namespace, $expected, $reference)
    {
        config(['blueprint.namespace' => $namespace]);

        $this->assertEquals($expected, Blueprint::relativeNamespace($reference));
    }

    public function namespacesDataProvider()
    {
        return [
            ['App', 'Models\User', 'App\Models\User'],
            ['App', 'Models\User', '\App\Models\User'],
            ['App', 'Some\Other\Reference', 'Some\Other\Reference'],
            ['App', 'App\Appointments', 'App\App\Appointments'],
            ['Foo', 'Bar', 'Foo\Bar'],
            ['Foo', 'Foo\Bar', '\Foo\Foo\Bar'],
        ];
    }
}
