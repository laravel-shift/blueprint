<?php

namespace Tests\Unit\Models\Statements;

use Blueprint\Models\Statements\EloquentStatement;
use PHPUnit\Framework\TestCase;

/**
 * @see EloquentStatement
 */
class EloquentStatementTest extends TestCase
{
    /**
     * @test
     */
    public function output_generates_code_for_find()
    {
        $subject = new EloquentStatement('find', 'user.id');

        $this->assertEquals('$user = User::find($id);', $subject->output('', 'whatever'));
    }

    /**
     * @test
     */
    public function output_generates_code_for_find_without_reference()
    {
        $subject = new EloquentStatement('find', 'id');

        $this->assertEquals('$post = Post::find($id);', $subject->output('Post', 'whatever'));
    }

    /**
     * @test
     */
    public function output_generates_code_for_save()
    {
        $subject = new EloquentStatement('save', 'post');

        $this->assertEquals('$post->save();', $subject->output('', 'whatever'));
    }

    /**
     * @test
     */
    public function output_generates_code_for_save_using_create()
    {
        $subject = new EloquentStatement('save', 'Post');

        $this->assertEquals('$post = Post::create($request->all());', $subject->output('', 'store'));
    }

    /**
     * @test
     */
    public function output_generates_code_for_save_using_create_when_validation_is_used()
    {
        $subject = new EloquentStatement('save', 'Post');

        $this->assertEquals('$post = Post::create($request->validated());', $subject->output('', 'store', true));
    }

    /**
     * @test
     */
    public function output_generates_code_for_update_using_model()
    {
        $subject = new EloquentStatement('update', 'post');

        $this->assertEquals('$post->update([]);', $subject->output('', ''));
    }

    /**
     * @test
     */
    public function output_generates_code_for_update_using_column_list()
    {
        $subject = new EloquentStatement('update', null, ['name', 'title', 'age']);

        $this->assertEquals('$user->update([\'name\' => $name, \'title\' => $title, \'age\' => $age]);', $subject->output('User', ''));
    }

    /**
     * @test
     */
    public function output_generates_code_for_delete()
    {
        $subject = new EloquentStatement('delete', 'post');

        $this->assertEquals('$post->delete();', $subject->output('', 'whatever'));
    }

    /**
     * @test
     */
    public function output_generates_code_for_delete_uses_delete_without_reference()
    {
        $subject = new EloquentStatement('delete', '');

        $this->assertEquals('$comment->delete();', $subject->output('Comment', 'whatever'));
    }

    /**
     * @test
     */
    public function output_generates_code_for_delete_using_destroy()
    {
        $subject = new EloquentStatement('delete', 'comment.id');

        $this->assertEquals('Comment::destroy($comment->id);', $subject->output('', 'whatever'));
    }
}
