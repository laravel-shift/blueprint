<?php

namespace Tests\Unit\Models\Statements;

use Blueprint\Models\Statements\QueryStatement;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @see QueryStatement
 */
final class QueryStatementTest extends TestCase
{
    #[Test]
    public function output_generates_code_for_all(): void
    {
        $subject = new QueryStatement('all', ['posts']);

        $this->assertEquals('$posts = Post::all();', $subject->output(''));
    }

    #[Test]
    public function output_generates_code_for_all_without_reference(): void
    {
        $subject = new QueryStatement('all');

        $this->assertEquals('$posts = Post::all();', $subject->output('Post'));
    }

    #[Test]
    public function output_generates_code_for_get_while_condensing_qualified_columns(): void
    {
        $subject = new QueryStatement('get', ['order:post.published_at']);

        $this->assertEquals('$posts = Post::orderBy(\'published_at\')->get();', $subject->output('Post'));
    }

    #[Test]
    public function output_generates_code_for_pluck_while_condensing_qualified_columns(): void
    {
        $subject = new QueryStatement('pluck', ['where:post.title', 'pluck:id']);

        $this->assertEquals('$post_ids = Post::where(\'title\', $post->title)->pluck(\'id\');', $subject->output('Post'));
    }

    #[Test]
    public function output_generates_code_for_count_while_preserving_qualified_columns(): void
    {
        $subject = new QueryStatement('count', ['where:post.title']);

        $this->assertEquals('$comment_count = Comment::where(\'post.title\', $post->title)->count();', $subject->output('Comment'));
    }
}
