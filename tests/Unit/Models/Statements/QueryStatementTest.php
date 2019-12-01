<?php

namespace Tests\Feature\Generators;

use Blueprint\Models\Statements\QueryStatement;
use Tests\TestCase;

/**
 * @see QueryStatement
 */
class QueryStatementTest extends TestCase
{
    /**
     * @test
     */
    public function output_generates_code_for_all()
    {
        $subject = new QueryStatement('all', ['posts']);

        $this->assertEquals('$posts = Post::all();', $subject->output(''));
    }

    /**
     * @test
     */
    public function output_generates_code_for_all_without_reference()
    {
        $subject = new QueryStatement('all');

        $this->assertEquals('$posts = Post::all();', $subject->output('Post'));
    }

    /**
     * @test
     */
    public function output_generates_code_for_get_while_condensing_qualified_columns()
    {
        $subject = new QueryStatement('get', ['order:post.published_at']);

        $this->assertEquals('$posts = Post::orderBy(\'published_at\')->get();', $subject->output('Post'));
    }

    /**
     * @test
     */
    public function output_generates_code_for_pluck_while_condensing_qualified_columns()
    {
        $subject = new QueryStatement('pluck', ['where:post.title', 'pluck:id']);

        $this->assertEquals('$post_ids = Post::where(\'title\', $post->title)->pluck(\'id\');', $subject->output('Post'));
    }

    /**
     * @test
     */
    public function output_generates_code_for_count_while_preserving_qualified_columns()
    {
        $subject = new QueryStatement('count', ['where:post.title']);

        $this->assertEquals('$comment_count = Comment::where(\'post.title\', $post->title)->count();', $subject->output('Comment'));
    }
}
