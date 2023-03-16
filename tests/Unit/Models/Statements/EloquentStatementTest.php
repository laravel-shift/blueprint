<?php

namespace Tests\Unit\Models\Statements;

use PHPUnit\Framework\Attributes\Test;
use Blueprint\Models\Statements\EloquentStatement;
use PHPUnit\Framework\TestCase;

/**
 * @see EloquentStatement
 */
final class EloquentStatementTest extends TestCase
{
    #[Test]
    public function output_generates_code_for_find(): void
    {
        $subject = new EloquentStatement('find', 'user.id');

        $this->assertEquals('$user = User::find($id);', $subject->output('', 'whatever'));
    }

    #[Test]
    public function output_generates_code_for_find_without_reference(): void
    {
        $subject = new EloquentStatement('find', 'id');

        $this->assertEquals('$post = Post::find($id);', $subject->output('Post', 'whatever'));
    }

    #[Test]
    public function output_generates_code_for_save(): void
    {
        $subject = new EloquentStatement('save', 'post');

        $this->assertEquals('$post->save();', $subject->output('', 'whatever'));
    }

    #[Test]
    public function output_generates_code_for_save_using_create(): void
    {
        $subject = new EloquentStatement('save', 'Post');

        $this->assertEquals('$post = Post::create($request->all());', $subject->output('', 'store'));
    }

    #[Test]
    public function output_generates_code_for_save_using_create_when_validation_is_used(): void
    {
        $subject = new EloquentStatement('save', 'Post');

        $this->assertEquals('$post = Post::create($request->validated());', $subject->output('', 'store', true));
    }

    #[Test]
    public function output_generates_code_for_update_using_model(): void
    {
        $subject = new EloquentStatement('update', 'post');

        $this->assertEquals('$post->update([]);', $subject->output('', ''));
    }

    #[Test]
    public function output_generates_code_for_update_using_column_list(): void
    {
        $subject = new EloquentStatement('update', null, ['name', 'title', 'age']);

        $this->assertEquals('$user->update([\'name\' => $name, \'title\' => $title, \'age\' => $age]);', $subject->output('User', ''));
    }

    #[Test]
    public function output_generates_code_for_delete(): void
    {
        $subject = new EloquentStatement('delete', 'post');

        $this->assertEquals('$post->delete();', $subject->output('', 'whatever'));
    }

    #[Test]
    public function output_generates_code_for_delete_uses_delete_without_reference(): void
    {
        $subject = new EloquentStatement('delete', '');

        $this->assertEquals('$comment->delete();', $subject->output('Comment', 'whatever'));
    }

    #[Test]
    public function output_generates_code_for_delete_using_destroy(): void
    {
        $subject = new EloquentStatement('delete', 'comment.id');

        $this->assertEquals('Comment::destroy($comment->id);', $subject->output('', 'whatever'));
    }
}
